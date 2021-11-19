#!/usr/bin/env bash

# Break on any error or undefined variable.
set -eu

SCRIPTS_DIR=$(cd "$(dirname "$0")"; pwd)
MODULE_DIR=$(realpath "${SCRIPTS_DIR}/..")
# Use the GUTENBERG_REPO_DIR environment variable as the default if available.
REPO_DIR=${GUTENBERG_REPO_DIR:-"$MODULE_DIR/.gutenberg"}
GIT_REPO="https://github.com/WordPress/gutenberg.git"
GIT_VERSION=master
GIT_FETCH_ALL=${GIT_FETCH_ALL:-0}
CHECKOUT_BRANCH="${GIT_VERSION}"
CLEAN_REPO=0
GIT_TAG=0
BUILD_MODE='production'

display_help() {
  script="$0"
  echo "Script for generating Gutenberg asset files..."
  echo "Usage: $script [repo_directory] [options...]" >&2;
  echo
  echo "   [repo_directory]               The directory to clone Gutenberg into. Defaults to GUTENBERG_REPO_DIR environment variable or [.gutenberg]"
  echo "                                  If using \`.gutenberg\`, you might want to add '.gutenberg' to your local \`\$settings['file_scan_ignore_directories']\`"
  echo
  echo "   --version {version}            The Gutenberg version to pull e.g. 'v7.8.1'. The 'v' prefix is important. Defaults to [master]"
  echo "   --clean                        Flag indicating that the repo should be cleaned. Useful when switching between versions and the node_modules need to be purged."
  echo "   --fetch-all                    Whether to fetch all branches and tags from the remote repository."
  echo "   --dev                          Flag indicating that a dev version of the assets should be generated. This typically enables source-map support."
  echo
  echo "   -h, --help                     This help text"
  echo
  echo "Example usage:"
  echo
  echo "  Install Gutenberg 7.8.1 in development mode:"
  echo
  echo "    $script --version v7.8.1 --dev"
  echo
}

POSITIONAL=()
while (( "$#" )); do
  case "$1" in
    --version|--version=*) value="${1##--version=}"
      if [[ "$value" != "$1" ]]; then shift; else value="$2"; shift 2; fi
      GIT_VERSION="$value";
      if [[ "${GIT_VERSION}" =~ ^v[0-9] ]]; then
        GIT_TAG=1
        CHECKOUT_BRANCH="branch-${GIT_VERSION}"
      else
        CHECKOUT_BRANCH="${GIT_VERSION}"
      fi
    ;;
    --fetch-all)
      GIT_FETCH_ALL=1; shift
    ;;
    --clean)
      CLEAN_REPO=1; shift
    ;;
    --dev)
      BUILD_MODE=dev; shift
    ;;

    --help | -h)
      display_help
      exit 0;
    ;;
    *)
      POSITIONAL+=( "$1" )
      shift
    ;;
  esac
done

set -- "${POSITIONAL[@]}" # restore unmatched positional parameters

if [[ "$#" -gt 0 ]]; then
  REPO_DIR="$1";
fi

mkdir -p "${REPO_DIR}";

if [[ -d "${REPO_DIR}/.git" ]]; then
  # Folder has already been initialized. We can do a reset and pull ourselves.
  echo "The Gutenberg repo already exists on disk 😊..."
else
  echo "The Gutenberg repo does not exist on disk..."
  echo "Cloning repo (will take a while 😣)..."
  git clone "${GIT_REPO}" "${REPO_DIR}"
fi

# Initialize the Gutenberg repo.
cd "${REPO_DIR}"

if [[ "$GIT_VERSION" = 'master' ]]; then
  # This is probably a mistake, as builds should be done against version tags.
  echo "🚨 About to build Gutenberg for the $GIT_VERSION branch! Are you sure? 🚨"
  echo -n "[y]es/[N]o: "
  read answer
  if [[ ! "$answer" =~ ^([yY]) ]]; then
    exit
  fi
fi

if [[ "${GIT_FETCH_ALL}" = 1 ]]; then
  echo "Fetch all upstream branches and tags (might be really slow)"
  git fetch --all --tags
else
  if [[ "${GIT_TAG}" = 1 ]]; then
    echo "Fetching ${GIT_VERSION} tag from origin..."
    git fetch origin "refs/tags/${GIT_VERSION}"
  else
    echo "Fetching ${GIT_VERSION} branch from origin..."
    git fetch origin "${GIT_VERSION}"
  fi
fi

echo "Git reset hard"
git reset --hard HEAD

# Checkout or create the relevant branch.
if git rev-parse --verify --quiet "${CHECKOUT_BRANCH}"; then
  echo "Checking out existing branch."
  git checkout "${CHECKOUT_BRANCH}"
else
  if [[ "${GIT_TAG}" = 1 ]]; then
    # Checking out a version tag.
    echo "Checkout version tag: ${GIT_VERSION}"
    git checkout tags/"${GIT_VERSION}" -b "${CHECKOUT_BRANCH}"
  else
    echo "Checkout branch: ${CHECKOUT_BRANCH}"
    git checkout --track origin/"${CHECKOUT_BRANCH}" -b "${CHECKOUT_BRANCH}"
  fi
fi

git pull origin "${GIT_VERSION}"

if [[ "${CLEAN_REPO}" = 1 ]]; then
  to_clean=$(git clean -xdf --dry-run)
  if [[ ! -z "$to_clean" ]]; then
    echo "$to_clean"
    echo "🚨 About to delete above files in the $REPO_DIR folder! Is this okay? 🚨"
    echo -n "[y]es/[N]o: "
    read answer
    #if [[ "$answer" =~ ^([yY]) ]]; then
    if [[ "$answer" != "${answer#[Yy]}" ]]; then
      # Remove ignored files to reset repository to pristine condition. Previous
      # test ensures that changed files abort the plugin build.
      echo "Cleaning working directory... 🛀"
      git clean -xdf
    fi
  fi
fi

# Run npm install.
# npm 6.9+ is required due to https://github.com/WordPress/gutenberg/pull/18048#discussion_r337402830
echo "Running npm install..."
npm install

echo "Building assets..."
rm "${REPO_DIR}/build" -rf
if [[ "${BUILD_MODE}" = 'production' ]]; then
  npm run build
else
  # Replicate 'npm run dev' without having to watch.
  npm run build:packages
  node "${REPO_DIR}/node_modules/webpack/bin/webpack.js"
fi

# Switch the working directory to the module.
cd "${MODULE_DIR}"

echo "Copying compiled assets..."

# Copy the built assets over to the vendor folder.
rm -rf "${MODULE_DIR}/vendor/gutenberg" &&  \
 cp -r "${REPO_DIR}/build" "${MODULE_DIR}/vendor/gutenberg"

# Regenerate the dependencies.
php "${SCRIPTS_DIR}/gutenberg-dependencies.php"

# Update the vendor scripts.
php "${SCRIPTS_DIR}/generate-vendor.php"

# Update the Gutenberg JS version constants in 'gutenberg.module'.
php "${SCRIPTS_DIR}/write-gutenberg-version.php" "${REPO_DIR}" --mode="$BUILD_MODE"
