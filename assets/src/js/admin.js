/**
 * Adds progressive disclosure to the Environment Guard settings screen.
 */

import '../scss/admin.scss';

/**
 * Keeps conditional settings regions in sync with their radio groups.
 */
class EnvironmentGuardSettings {
	/**
	 * Enhances each settings screen present in the document.
	 *
	 * @return {void}
	 */
	static initializeAll() {
		document
			.querySelectorAll( '[data-environment-guard-settings]' )
			.forEach( ( root ) =>
				new EnvironmentGuardSettings( root ).initialize()
			);
	}

	/**
	 * Creates a settings screen enhancement.
	 *
	 * @param {HTMLElement} root Settings screen root element.
	 */
	constructor( root ) {
		this.root = root;
		this.regions = Array.from(
			root.querySelectorAll( '[data-environment-guard-conditional]' )
		);
	}

	/**
	 * Starts listening for setting changes.
	 *
	 * @return {void}
	 */
	initialize() {
		this.regions.forEach( ( region ) => {
			this.controlsFor( region ).forEach( ( control ) => {
				control.addEventListener( 'change', () =>
					this.update( region )
				);
			} );

			this.update( region );
		} );
	}

	/**
	 * Finds radio controls associated with a conditional region.
	 *
	 * @param {HTMLElement} region Conditional settings region.
	 * @return {HTMLInputElement[]} Associated radio controls.
	 */
	controlsFor( region ) {
		const regionName = region.dataset.environmentGuardConditional;
		const controls = this.root.querySelectorAll(
			'[data-environment-guard-controls]'
		);

		return Array.from( controls ).filter(
			( control ) =>
				control.dataset.environmentGuardControls === regionName
		);
	}

	/**
	 * Updates visibility and native validation for one conditional region.
	 *
	 * @param {HTMLElement} region Conditional settings region.
	 * @return {void}
	 */
	update( region ) {
		const selectedControl = this.controlsFor( region ).find(
			( control ) => control.checked
		);
		const isVisible =
			selectedControl?.value === region.dataset.environmentGuardShowWhen;

		region.hidden = ! isVisible;

		region
			.querySelectorAll( '[data-environment-guard-required]' )
			.forEach( ( input ) => {
				input.required = isVisible;
			} );
	}
}

if ( 'loading' === document.readyState ) {
	document.addEventListener(
		'DOMContentLoaded',
		EnvironmentGuardSettings.initializeAll
	);
} else {
	EnvironmentGuardSettings.initializeAll();
}

export { EnvironmentGuardSettings };
