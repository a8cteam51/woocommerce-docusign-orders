import { createHooks } from '@wordpress/hooks';
import domReady from '@wordpress/dom-ready';

window.wpcomsp_dwo = window.wpcomsp_dwo || {};
window.wpcomsp_dwo.hooks = createHooks();

domReady( () => {
	window.wpcomsp_dwo.hooks.doAction( 'editor.ready' );
} );
