<?php

namespace Drupal\Tests\email_registration\Functional;

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests the email registration module.
 *
 * @group email_registration
 */
class EmailRegistrationFunctionalTest extends EmailRegistrationFunctionalTestBase {

  /**
   * Tests if installing the module, won't break the site.
   */
  public function testInstallation() {
    $session = $this->assertSession();
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Tests if uninstalling the module, won't break the site.
   */
  public function testUninstallation() {
    $this->drupalLogin($this->adminUser);
    // Go to uninstallation page an uninstall email_registration_username:
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/modules/uninstall');
    $session->statusCodeEquals(200);
    $page->checkField('edit-uninstall-email-registration');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    // Confirm uninstall:
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The selected modules have been uninstalled.');
    // Retest the frontpage:
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Test various behaviors for anonymous users.
   */
  public function testRegistration() {
    $user_config = $this->container->get('config.factory')->getEditable('user.settings');
    $email_registration_config = $this->container->get('config.factory')->getEditable('email_registration.settings');
    $user_config
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();
    // Try to register a user.
    $name = $this->randomMachineName();
    $pass = $this->randomString(10);
    $register = [
      'mail' => $name . '@example.com',
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    ];
    $this->drupalGet('/user/register');
    $this->submitForm($register, 'Create new account');
    $this->drupalLogout();

    $login = [
      'name' => $name . '@example.com',
      'pass' => $pass,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($login, 'Log in');

    // Really basic confirmation that the user was created and logged in.
    $this->assertSession()->responseContains('<title>' . $name . ' | Drupal</title>');

    // Now try the immediate login.
    $this->drupalLogout();

    // Try to login with just username, should fail by default.
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains('Enter your email address.');
    $this->assertSession()->responseContains('Email');
    $this->assertSession()->responseNotContains('Email or username');
    $login = [
      'name' => $name,
      'pass' => $pass,
    ];
    $this->submitForm($login, 'Log in');
    // When login_with_username is false, a user cannot login with just their
    // username.
    $this->assertSession()->responseContains('Unrecognized email address or password.');

    // Set login_with_username to TRUE and try to login with just username.
    $email_registration_config->set('login_with_username', TRUE)->save();
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains('Enter your email address or username.');
    $this->assertSession()->responseContains('Email or username');
    $this->submitForm($login, 'Log in');
    // When login_with_username is true, a user can login with just their
    // username.
    $this->assertSession()->responseContains('<title>' . $name . ' | Drupal</title>');
    $this->drupalLogout();

    $user_config
      ->set('verify_mail', FALSE)
      ->save();
    $name = $this->randomMachineName();
    $pass = $this->randomString(10);
    $register = [
      'mail' => $name . '@example.com',
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    ];
    $this->drupalGet('/user/register');
    $this->submitForm($register, 'Create new account');
    // User properly created, immediately logged in.
    $this->assertSession()->responseContains('Registration successful. You are now logged in.');

    // Test email_registration_unique_username().
    $this->drupalLogout();
    $user_config
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();
    $name = $this->randomMachineName(32);
    $pass = $this->randomString(10);

    $this->createUser([], $name);
    $next_unique_name = email_registration_unique_username($name);

    $register = [
      'mail' => $name . '@example2.com',
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    ];
    $this->drupalGet('/user/register');
    $this->submitForm($register, 'Create new account');
    $account = user_load_by_mail($register['mail']);
    $this->assertSame($next_unique_name, $account->getAccountName());
    $this->drupalLogout();

    // Check if custom username stays the same when user is edited.
    $user = $this->createUser();
    $name = $user->label();
    $this->drupalLogin($user);
    $this->drupalGet('/user/' . $user->id() . '/edit');
    $this->submitForm([], 'Save');
    $this->assertEquals($name, User::load($user->id())->label(), 'Username should not change after empty edit.');
    $this->drupalLogout();
    $this->drupalLogin($user);
    $this->assertSame($next_unique_name, $account->getAccountName());
  }

  /**
   * Test the "change own username" permission and user edit save.
   */
  public function testUsernamePermissions() {
    // Set login_with_username to TRUE for $this->>drupalLogin.
    $this->container->get('config.factory')
      ->getEditable('email_registration.settings')
      ->set('login_with_username', TRUE)
      ->save(TRUE);

    $user = $this->createUser(['change own username']);
    $this->drupalLogin($user);
    $this->drupalGet('user/' . $user->id() . '/edit');
    $this->assertSession()->fieldExists('edit-name');

    $this->drupalLogout();

    $user = $this->createUser();
    $username = $user->getAccountName();

    $this->drupalLogin($user);
    $this->drupalGet('user/' . $user->id() . '/edit');
    // Test that the field is set to type=value.
    $this->assertSession()->fieldNotExists('edit-name');
    $this->assertSession()->pageTextContains($username);
    // Make sure the email isn't changed on save.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains($username);
  }

  /**
   * Tests the options to allow the username on registration.
   */
  public function testAllowUsernameRegistration() {
    \Drupal::configFactory()->getEditable('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();
    \Drupal::configFactory()->getEditable('email_registration.settings')
      ->set('require_username_on_registration', TRUE)
      ->save();

    $name = strtolower($this->randomMachineName());
    $pass = $this->randomString();

    $this->drupalGet('/user/register');
    $assert_session = $this->assertSession();
    $session = $this->getSession();
    $page = $session->getPage();

    $assert_session->fieldExists('Email');
    $assert_session->fieldExists('Username');
    $assert_session->fieldExists('Password');
    $assert_session->fieldExists('Confirm password');

    // Omit the username.
    $page->fillField('Email', "$name@example.com");
    $page->fillField('Password', $pass);
    $page->fillField('Confirm password', $pass);
    $page->pressButton('Create new account');

    // Check that the username field is required.
    $assert_session->pageTextContains('Username field is required.');

    $page->fillField('Username', $name);
    $page->fillField('Password', $pass);
    $page->fillField('Confirm password', $pass);
    $page->pressButton('Create new account');

    $assert_session->pageTextContains('Registration successful. You are now logged in.');

    $this->drupalGet('/user');
    $assert_session->pageTextContains($name);

    // Extract the user ID from the current URL and load the user from backend.
    preg_match('/(\d+)$/', $this->getSession()->getCurrentUrl(), $found);
    $account = User::load($found[1]);

    // Check that the name entered by the user has been assigned.
    $this->assertEquals($name, $account->getAccountName());

    // Log out.
    $this->drupalLogout();
    $this->drupalGet('/user/login');

    // Check that the username is not present on the login form.
    $assert_session->fieldNotExists('Username');

    $page->fillField('Email', "$name@example.com");
    $page->fillField('Password', $pass);
    $page->pressButton('Log in');

    // Check that the user is logged in.
    $assert_session->pageTextContains($name);

    \Drupal::configFactory()->getEditable('email_registration.settings')
      ->set('login_with_username', TRUE)
      ->save();

    // Log out.
    $this->drupalLogout();
    $this->drupalGet('/user/login');

    // Login with username.
    $page->fillField('Email or username', $name);
    $page->fillField('Password', $pass);
    $page->pressButton('Log in');

    // Check that the user is logged in.
    $assert_session->pageTextContains($name);

    // Log out.
    $this->drupalLogout();
    $this->drupalGet('/user/login');

    // Login with email.
    $page->fillField('Email or username', "$name@example.com");
    $page->fillField('Password', $pass);
    $page->pressButton('Log in');

    // Check that the user is logged in.
    $assert_session->pageTextContains($name);
  }

  /**
   * Tests the "email_registration_update_username" action.
   */
  public function testBatchAction() {
    // Rename both the "user" and "adminUser":
    $this->user->setUserName('testUser1');
    $this->user->setEmail('testA@mail.com');
    $this->user->save();
    $this->adminUser->setUserName('testUser2');
    $this->adminUser->setEmail('testB@mail.com');
    $this->adminUser->save();

    // Execute our action on the users:
    $updateUsernameAction = \Drupal::entityTypeManager()
      ->getStorage('action')
      ->load('email_registration_update_username');
    $updateUsernameAction->execute([$this->user, $this->adminUser]);

    // The active users username should be a stripped variant of their
    // email-address:
    $this->assertSame('testA', $this->user->getAccountName());
    $this->assertSame('testB', $this->adminUser->getAccountName());
  }

  /**
   * Tests the "email_registration_update_username" action.
   */
  public function testBatchActionAlreadyExistingName() {
    // Rename both the "user" and "adminUser":
    $this->user->setUserName('testUser1');
    $this->user->save();
    $this->adminUser->setUserName('testUser2');
    $this->adminUser->save();
    // Execute our action on the users:
    $updateUsernameAction = \Drupal::entityTypeManager()
      ->getStorage('action')
      ->load('email_registration_update_username');
    $updateUsernameAction->execute([$this->user, $this->adminUser]);
    // The active "user" username should be a stripped variant of their
    // email-address:
    $this->assertSame('user', $this->user->getAccountName());
    // Since there is already an 'admin' user through BrowserTestBase,
    // this user will be named 'admin_1':
    $this->assertSame('admin_1', $this->adminUser->getAccountName());
  }

}
