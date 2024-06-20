/**
 * @file
 * The JavaScript file for Bootstrap Paragraphs Accordion.
 */

document.addEventListener('DOMContentLoaded', function () {
  // Get all accordion wrappers on the page and call the expand/collapse all function.
  const accordionWrappers = document.querySelectorAll('.accordion-wrapper');
  accordionWrappers.forEach(function (currentWrapper) {
    expandCollapseAllAccordion(currentWrapper);
  });

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

    // This function expands/collapse all accordion panels within the current accordion wrapper.
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
    }
  }
});
