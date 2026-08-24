import { EnvironmentGuardSettings } from '../../assets/src/js/admin';

describe( 'EnvironmentGuardSettings', () => {
	let root;

	beforeEach( () => {
		document.body.innerHTML = `
			<div data-environment-guard-settings>
				<label><input data-environment-guard-controls="mail-redirect" name="mail-mode" type="radio" value="block" checked>Block</label>
				<label><input data-environment-guard-controls="mail-redirect" name="mail-mode" type="radio" value="redirect">Redirect</label>
				<div data-environment-guard-conditional="mail-redirect" data-environment-guard-show-when="redirect">
					<input data-environment-guard-required type="email">
				</div>
			</div>
		`;
		root = document.querySelector( '[data-environment-guard-settings]' );
	} );

	afterEach( () => {
		document.body.replaceChildren();
	} );

	test( 'hides an inactive region and removes native validation', () => {
		const region = root.querySelector(
			'[data-environment-guard-conditional]'
		);
		const input = region.querySelector(
			'[data-environment-guard-required]'
		);

		new EnvironmentGuardSettings( root ).initialize();

		expect( region.hidden ).toBe( true );
		expect( input.required ).toBe( false );
	} );

	test( 'shows the selected region and restores native validation', () => {
		const enhancement = new EnvironmentGuardSettings( root );
		const redirect = root.querySelector( '[value="redirect"]' );
		const region = root.querySelector(
			'[data-environment-guard-conditional]'
		);
		const input = region.querySelector(
			'[data-environment-guard-required]'
		);

		enhancement.initialize();
		redirect.checked = true;
		redirect.dispatchEvent( new Event( 'change' ) );

		expect( region.hidden ).toBe( false );
		expect( input.required ).toBe( true );
	} );

	test( 'ignores controls assigned to another region', () => {
		const unrelated = document.createElement( 'input' );
		unrelated.dataset.environmentGuardControls = 'http-allowlist';
		root.append( unrelated );
		const region = root.querySelector(
			'[data-environment-guard-conditional]'
		);

		const enhancement = new EnvironmentGuardSettings( root );

		expect( enhancement.controlsFor( region ) ).toHaveLength( 2 );
	} );
} );
