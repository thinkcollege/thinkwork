/* eslint func-names: ["error", "never"] */
(function(Drupal, DrupalGutenberg, drupalSettings, wp, $) {
  const { useSelect, useDispatch } = wp.data;
  const { ExternalLink, Guide, GuidePage } = wp.components;
  const { __ } = wp.i18n;
  const { createInterpolateElement, render } = wp.element;

  const CanvasImage = props => (
    <img
      alt=""
      src="data:image/svg+xml,%3Csvg width='306' height='286' viewBox='0 0 306 286' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='306' height='286' rx='4' fill='%230678BE'/%3E%3Cpath d='M45 32C45 30.8954 45.8954 30 47 30H259C260.105 30 261 30.8954 261 32V286H45V32Z' fill='white'/%3E%3Crect x='45' y='80' width='216' height='94' fill='%23DDDDDD'/%3E%3Crect x='144.75' y='118.75' width='16.5' height='16.5' rx='1.53571' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M151 130V124L156 127L151 130Z' fill='black'/%3E%3Crect x='57' y='182' width='91.4727' height='59' fill='%23DDDDDD'/%3E%3Crect x='156.982' y='182' width='91.4727' height='59' fill='%23DDDDDD'/%3E%3Crect x='94.75' y='203.75' width='16.5' height='16.5' rx='1.25' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M95 216L99.5714 212.667L103 214.889L107 211L111 214.889' stroke='black' stroke-width='1.5' stroke-linejoin='round'/%3E%3Crect x='194.75' y='203.75' width='16.5' height='16.5' rx='1.25' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M195 216L199.571 212.667L203 214.889L207 211L211 214.889' stroke='black' stroke-width='1.5' stroke-linejoin='round'/%3E%3Crect x='57' y='38' width='191.455' height='34' fill='%23DDDDDD'/%3E%3Cpath d='M148 49L148 60L153 55.875L158 60L158 49L153 49L148 49Z' fill='black' stroke='black' stroke-width='1.5'/%3E%3Crect x='58' y='249' width='191' height='37' fill='%23DDDDDD'/%3E%3Cline x1='155.917' y1='281' x2='155.917' y2='265.889' stroke='black' stroke-width='1.5'/%3E%3Cline x1='151.472' y1='281' x2='151.472' y2='265.889' stroke='black' stroke-width='1.5'/%3E%3Cline x1='159.333' y1='265.75' x2='150.445' y2='265.75' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M150.139 269.889V273.96C148.211 273.607 146.75 271.919 146.75 269.889C146.75 267.859 148.211 266.171 150.139 265.818V269.889Z' fill='black' stroke='black' stroke-width='1.5'/%3E%3C/svg%3E%0A"
      {...props}
    />
  );
  const EditorImage = props => (
    <img
      alt=""
      src="data:image/svg+xml,%3Csvg width='306' height='286' viewBox='0 0 306 286' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='306' height='286' rx='4' fill='%230678BE'/%3E%3Crect x='45' y='99' width='216' height='108' rx='2' fill='white'/%3E%3Cline x1='155.917' y1='161' x2='155.917' y2='145.889' stroke='black' stroke-width='1.5'/%3E%3Cline x1='151.472' y1='161' x2='151.472' y2='145.889' stroke='black' stroke-width='1.5'/%3E%3Cline x1='159.333' y1='145.75' x2='150.445' y2='145.75' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M150.139 149.889V153.96C148.211 153.607 146.75 151.919 146.75 149.889C146.75 147.859 148.211 146.171 150.139 145.818V149.889Z' fill='black' stroke='black' stroke-width='1.5'/%3E%3Crect x='45.5' y='72.5' width='117' height='21' rx='1.5' fill='white' stroke='%231E1E1E'/%3E%3Cline x1='67.5918' y1='72' x2='67.5918' y2='94' stroke='%231E1E1E'/%3E%3Cline x1='141.432' y1='72' x2='141.432' y2='94' stroke='%231E1E1E'/%3E%3Cpath d='M63.4121 93.5417L67.0921 89.875V93.5417H63.4121Z' fill='%231E1E1E'/%3E%3Crect x='52' y='79' width='9' height='8' rx='1' fill='%23333333'/%3E%3Crect x='147' y='82' width='2' height='2' fill='%23333333'/%3E%3Crect x='151' y='82' width='2' height='2' fill='%23333333'/%3E%3Crect x='155' y='82' width='2' height='2' fill='%23333333'/%3E%3C/svg%3E%0A"
      {...props}
    />
  );

  const BlockLibraryImage = props => (
    <img
      alt=""
      src="data:image/svg+xml,%3Csvg width='306' height='286' viewBox='0 0 306 286' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='306' height='286' rx='4' fill='%230678BE'/%3E%3Crect x='58' y='44' width='21.3714' height='21.3714' rx='2' fill='%231E1E1E'/%3E%3Cpath d='M68.75 49V60M63 54.25H74' stroke='white' stroke-width='1.5'/%3E%3Crect x='48' y='71' width='210' height='154' rx='2' fill='white'/%3E%3Cpath d='M214 181L214 192L219 187.875L224 192L224 181L219 181L214 181Z' fill='black' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M162 183V194C162 195.105 161.106 196 160.001 196C156.897 196 150.874 196 147 196' stroke='black' stroke-width='1.5'/%3E%3Crect x='144.75' y='178.75' width='13.5' height='13.5' rx='0.875' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M145 189L148.714 187L151.5 188.333L154.75 186L158 188.333' stroke='black' stroke-width='1.5' stroke-linejoin='round'/%3E%3Crect x='79.75' y='178.75' width='16.5' height='16.5' rx='1.25' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M80 191L84.5714 187.667L88 189.889L92 186L96 189.889' stroke='black' stroke-width='1.5' stroke-linejoin='round'/%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M161 131.5H145V130H161V131.5Z' fill='black'/%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M161 138.5H153V137H161V138.5Z' fill='black'/%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M161 146H145V144.5H161V146Z' fill='black'/%3E%3Ccircle cx='147' cy='138' r='2' fill='black'/%3E%3Cpath d='M220 130H226V136H220V130Z' fill='black'/%3E%3Cpath d='M211 130H217V136H211V130Z' fill='black'/%3E%3Cpath d='M226 130C226 133.332 226 136.8 226 139.999C226 143.198 223.5 142.997 222 142.998' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M217 130C217 133.332 217 136.8 217 139.999C217 143.198 214.5 142.997 213 142.998' stroke='black' stroke-width='1.5'/%3E%3Cline x1='90.9165' y1='145' x2='90.9165' y2='129.889' stroke='black' stroke-width='1.5'/%3E%3Cline x1='86.4722' y1='145' x2='86.4722' y2='129.889' stroke='black' stroke-width='1.5'/%3E%3Cline x1='94.3335' y1='129.75' x2='85.4446' y2='129.75' stroke='black' stroke-width='1.5'/%3E%3Cpath d='M85.1389 133.889V137.96C83.2111 137.607 81.75 135.919 81.75 133.889C81.75 131.859 83.2111 130.171 85.1389 129.818V133.889Z' fill='black' stroke='black' stroke-width='1.5'/%3E%3Crect x='58.5' y='81.5' width='189' height='25' rx='3.5' stroke='%23007CA8'/%3E%3C/svg%3E%0A"
      {...props}
    />
  );

  const DocumentationImage = props => (
    <img
      alt=""
      src="data:image/svg+xml,%3Csvg%20width%3D'306'%20height%3D'286'%20viewBox%3D'0%200%20306%20286'%20fill%3D'none'%20%0A%20%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%3E%0A%20%20%3Crect%20width%3D'306'%20height%3D'286'%20rx%3D'4'%20fill%3D'%230678BE'%2F%3E%0A%20%20%3Cpath%20d%3D'M45%2032C45%2030.8954%2045.8954%2030%2047%2030H259C260.105%2030%20261%2030.8954%20261%2032V286H45V32Z'%20fill%3D'white'%2F%3E%0A%20%20%3Cpath%20d%3D'M45%2032C45%2030.8954%2045.8954%2030%2047%2030H259C260.105%2030%20261%2030.8954%20261%2032V72H45V32Z'%20fill%3D'%231E1E1E'%2F%3E%0A%20%20%3Crect%20x%3D'90'%20y%3D'98'%20width%3D'104'%20height%3D'16'%20fill%3D'%23DDDDDD'%2F%3E%0A%20%20%3Crect%20x%3D'90'%20y%3D'129'%20width%3D'126'%20height%3D'8'%20fill%3D'%23DDDDDD'%2F%3E%0A%20%20%3Crect%20x%3D'90'%20y%3D'142'%20width%3D'126'%20height%3D'8'%20fill%3D'%23DDDDDD'%2F%3E%0A%20%20%3Crect%20x%3D'90'%20y%3D'155'%20width%3D'126'%20height%3D'8'%20fill%3D'%23DDDDDD'%2F%3E%0A%20%20%3Crect%20x%3D'90'%20y%3D'209'%20width%3D'126'%20height%3D'8'%20fill%3D'%23DDDDDD'%2F%3E%0A%20%20%3Crect%20x%3D'90'%20y%3D'168'%20width%3D'126'%20height%3D'8'%20fill%3D'%23DDDDDD'%2F%3E%0A%20%20%3Crect%20x%3D'90'%20y%3D'222'%20width%3D'126'%20height%3D'8'%20fill%3D'%23DDDDDD'%2F%3E%0A%20%20%3Crect%20x%3D'90'%20y%3D'181'%20width%3D'63'%20height%3D'8'%20fill%3D'%23DDDDDD'%2F%3E%0A%20%20%3Crect%20x%3D'90'%20y%3D'235'%20width%3D'63'%20height%3D'8'%20fill%3D'%23DDDDDD'%2F%3E%0A%3C%2Fsvg%3E%0A"
      {...props}
    />
  );

  const InserterIconImage = props => (
    <img
      alt={__('inserter')}
      src="data:image/svg+xml,%3Csvg width='18' height='18' viewBox='0 0 18 18' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect width='18' height='18' rx='2' fill='%231E1E1E'/%3E%3Cpath d='M9.22727 4V14M4 8.77273H14' stroke='white' stroke-width='1.5'/%3E%3C/svg%3E%0A"
      {...props}
    />
  );

  const WelcomeGuide = () => {
    const isDisabled = useSelect(
      select =>
        select('core/edit-post').isFeatureActive('welcomeGuideDrupalDisabled'),
      [],
    );

    const { toggleFeature } = useDispatch('core/edit-post');

    if (isDisabled) {
      return null;
    }

    return (
      <Guide
        className="edit-post-welcome-guide"
        contentLabel={__('Welcome to the block editor')}
        finishButtonText={__('Get started')}
        onFinish={() => toggleFeature('welcomeGuideDrupalDisabled')}
      >
        <GuidePage className="edit-post-welcome-guide__page">
          <h1 className="edit-post-welcome-guide__heading">
            {__('Welcome to the Gutenberg editor')}
          </h1>
          <CanvasImage className="edit-post-welcome-guide__image" />
          <p className="edit-post-welcome-guide__text">
            {__(
              'In the Gutenberg editor, each paragraph, image, or video is presented as a distinct “block” of content.',
            )}
          </p>
        </GuidePage>
        <GuidePage className="edit-post-welcome-guide__page">
          <h1 className="edit-post-welcome-guide__heading">
            { __( 'Make each block your own' ) }
          </h1>
          <EditorImage className="edit-post-welcome-guide__image" />
          <p className="edit-post-welcome-guide__text">
            { __(
              'Each block comes with its own set of controls for changing things like color, width, and alignment. These will show and hide automatically when you have a block selected.'
            ) }
          </p>
        </GuidePage>

        <GuidePage className="edit-post-welcome-guide__page">
          <h1 className="edit-post-welcome-guide__heading">
            { __( 'Get to know the block library' ) }
          </h1>
          <BlockLibraryImage className="edit-post-welcome-guide__image" />
          <p className="edit-post-welcome-guide__text">
            { createInterpolateElement(
              __(
                'All of the blocks available to you live in the block library. You’ll find it wherever you see the <InserterIconImage /> icon.'
              ),
              {
                InserterIconImage: (
                  <InserterIconImage className="edit-post-welcome-guide__inserter-icon" />
                ),
              }
            ) }
          </p>
        </GuidePage>

        <GuidePage className="edit-post-welcome-guide__page">
          <h1 className="edit-post-welcome-guide__heading">
            { __( 'Learn how to use Gutenberg editor' ) }
          </h1>
          <DocumentationImage className="edit-post-welcome-guide__image" />
          <p className="edit-post-welcome-guide__text">
            { __(
              'New to the Gutenberg editor? Want to learn more about using it? '
            ) }
            <ExternalLink
              href={ __(
                'https://www.drupal.org/docs/8/modules/gutenberg/how-to-use-gutenberg-editor'
              ) }
            >
              { __( "Here's a detailed guide." ) }
            </ExternalLink>
          </p>
        </GuidePage>

      </Guide>
    );
  };

  DrupalGutenberg = DrupalGutenberg || {};
  DrupalGutenberg.Components = DrupalGutenberg.Components || {};
  DrupalGutenberg.Components.WelcomeGuide = WelcomeGuide;

  const container = document.createElement('div');
  document.body.appendChild(container);

  setTimeout(() => {
    render(<WelcomeGuide />, container);
  });
})(Drupal, DrupalGutenberg, drupalSettings, wp, jQuery);
