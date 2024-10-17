/**
 * @file
 * The JavaScript file for Bootstrap Paragraphs Tabs.
 */

(function ($) {
  $(document).ready(function ($) {
    const tabs = document.querySelectorAll(
      '.paragraph--type--bp-tabs .nav-tabs > li > a',
    );
    const panels = document.querySelectorAll(
      '.paragraph--type--bp-tabs .tab-pane',
    );

    /*
     * Remove the tabindex attribute from the now active tab.
     * Adds tabindex="-1" to inactive tabs so that they are removed from
     * the focus order.
     */
    tabs.forEach((tab) =>
      tab.addEventListener('click', (e) => {
        e.target.parentElement.parentElement
          .querySelectorAll('li > a')
          .forEach((offTab) => offTab.setAttribute('tabindex', '-1'));
        e.target.removeAttribute('tabindex');
      }),
    );

    /*
     * Invoke the switchTab function when left or right arrow key is pressed.
     */
    tabs.forEach((tab) =>
      tab.addEventListener('keydown', (e) => {
        // Get the index of the current tab in the tabs node list
        const index = Array.prototype.indexOf.call(tabs, e.currentTarget);
        // Figure out what key was pressed to set the new index
        // Conditionals are added to validate if it's the first or last tab in the list
        let dir = null;
        if (e.key === 'ArrowLeft') {
          dir = index - 1 >= 0 ? index - 1 : tabs.length - 1;
        } else if (e.key === 'ArrowRight') {
          dir = index + 1 < tabs.length ? index + 1 : 0;
        } else if (e.key === 'ArrowDown') {
          dir = 'down';
        }
       
        if (dir !== null) {
          e.preventDefault();
          // If the down key is pressed, move focus to the open panel,
          // otherwise switch to the adjacent tab
          if (dir === 'down') {
            // Adds tabindex to the panel, otherwise it can't be focused
            panels[index].setAttribute('tabindex', '-1');
            panels[index].focus();
          } else {
            panels[index].removeAttribute('tabindex');
            switchTab(tabs[dir]);
          }
        }
      }),
    );

    // Make inactive tabs active by pressing left and right arrow keys.
    function switchTab(newTab) {
      newTab.focus();
      // Click method is triggered to make the tabpanel switch on key press
      newTab.click();
    }
  });
})(jQuery);
