/**
 * @file
 * The JavaScript file for Bootstrap Paragraphs Accordion.
 */

document.addEventListener('DOMContentLoaded', function () {
  // Get all accordion wrappers on the page.
  const accordionWrappers = document.querySelectorAll('.accordion-wrapper');

  accordionWrappers.forEach(function (currentWrapper) {
    expandCollapseAllAccordion(currentWrapper);

    // There is always one accordion opened on load so set every button to "Collapse All".
    allButtonsClosed(currentWrapper);
  });

  // This function expands/collapse all accordion panels within the current accordion wrapper.
  function expandCollapseAllAccordion(currentAccordion) {

    // Accordion variables to target for expand/collapse all.
    const accordionWrapper = currentAccordion;
    const buttonSelector = accordionWrapper.querySelector(
      '.bp-accordion-button',
    );
    const accordionButtons =
      accordionWrapper.querySelectorAll('.accordion-button');
    const accordionPanels =
      accordionWrapper.querySelectorAll('.panel-collapse');

    if (buttonSelector) {
      buttonSelector.addEventListener('click', function () {
        if (buttonSelector.textContent.trim() === 'Expand All') {
          buttonSelector.textContent = 'Collapse All';
          buttonSelector.title =
            'Click to collapse all accordions in this section.';

          // Toggle the proper classes and values on the accordion button and panel.
          accordionButtons.forEach(function (accordionButton) {
            accordionButton.classList.remove('collapsed');
            accordionButton.setAttribute('aria-expanded', 'true');
          });
          accordionPanels.forEach(function (accordionPanel) {
            accordionPanel.classList.add('show');
          });
        } else {
          buttonSelector.textContent = 'Expand All';
          buttonSelector.title =
            'Click to expand all accordions in this section.';

          // Toggle the proper classes and values on the accordion button and panel.
          accordionButtons.forEach(function (accordionButton) {
            accordionButton.classList.add('collapsed');
            accordionButton.setAttribute('aria-expanded', 'false');
          });
          accordionPanels.forEach(function (accordionPanel) {
            accordionPanel.classList.remove('show');
          });
        }
      });

      // Check if all buttons are open or closed after each change of a single accordion.
      accordionButtons.forEach((accordion) => {
        accordion.addEventListener('click', function () {
          checkAllOpenOrClosed(accordionButtons, buttonSelector);
        });
      });
    }
  }

  // This function sets button to "Collapse All" because there is always 1 accordion item open on load.
  function allButtonsClosed(currentAccordion) {

    // Accordion variables to target for expand/collapse all.
    const accordionWrapper = currentAccordion;
    const buttonSelector = accordionWrapper.querySelector(
      '.bp-accordion-button',
    );

    if (buttonSelector) {
      buttonSelector.textContent = 'Collapse All';
    }
  }

  // This function checks if all buttons are closed/open and updates Expand/Collapse All button text.
  function checkAllOpenOrClosed(accordionButtons, buttonSelector) {
    const allClosed = Array.from(accordionButtons).every((item) =>
      item.classList.contains('collapsed'),
    );

    if (allClosed) {
      buttonSelector.textContent = 'Expand All';
    } else {
      buttonSelector.textContent = 'Collapse All';
    }
  }

  // Call the expand/collapse all function for all accordions on the page.
  accordionWrappers.forEach(function (currentWrapper) {
    expandCollapseAllAccordion(currentWrapper);
  });
});
