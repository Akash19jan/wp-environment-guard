const AxeBuilder = require( '@axe-core/playwright' ).default;
const { expect, test } = require( '@playwright/test' );

/**
 * Authenticates a browser page against the disposable wp-env site.
 */
class WordPressAdminSession {
	/**
	 * Creates an admin-session helper.
	 *
	 * @param {import('@playwright/test').Page} page Browser page.
	 */
	constructor( page ) {
		this.page = page;
	}

	/**
	 * Logs in with wp-env's development credentials.
	 *
	 * @return {Promise<void>}
	 */
	async logIn() {
		await this.page.goto( '/wp-login.php' );
		await this.page
			.getByLabel( 'Username or Email Address' )
			.fill( 'admin' );
		await this.page
			.getByLabel( 'Password', { exact: true } )
			.fill( 'password' );
		await this.page.getByRole( 'button', { name: 'Log In' } ).click();
		await expect( this.page.locator( 'body' ) ).toHaveClass( /wp-admin/ );
	}
}

test( 'staging responses discourage indexing and expose a text status', async ( {
	page,
} ) => {
	const response = await page.goto( '/' );

	expect( response ).not.toBeNull();
	expect( response.status() ).toBe( 200 );
	expect( response.headers()[ 'x-robots-tag' ] ).toContain( 'noindex' );

	const banner = page.getByRole( 'complementary', {
		name: 'Non-production environment',
	} );

	await expect( banner ).toContainText( 'Staging environment' );
	await expect( banner ).toContainText( 'Safeguards' );

	const accessibility = await new AxeBuilder( { page } )
		.include( '.akash-aagrawal-environment-guard-banner' )
		.analyze();

	expect( accessibility.violations ).toEqual( [] );
} );

test( 'settings remain semantic and progressively enhanced', async ( {
	page,
} ) => {
	await new WordPressAdminSession( page ).logIn();
	await page.goto(
		'/wp-admin/options-general.php?page=wp-environment-guard'
	);

	await expect(
		page.getByRole( 'heading', { level: 1, name: 'Environment Guard' } )
	).toBeVisible();
	await expect(
		page.getByRole( 'group', { name: 'Outgoing email' } )
	).toBeVisible();

	const redirect = page.getByLabel( 'Redirect every message' );
	const safeSink = page.getByLabel( 'Safe redirect address' );

	await expect( safeSink ).toBeHidden();
	await redirect.check();
	await expect( safeSink ).toBeVisible();
	await expect( safeSink ).toHaveAttribute( 'required', '' );

	const accessibility = await new AxeBuilder( { page } )
		.include( '[data-environment-guard-settings]' )
		.analyze();

	expect( accessibility.violations ).toEqual( [] );
} );
