import $ from 'jquery';
import * as Helper from '@typo3/form/backend/form-editor/helper.js'

/**
 * @private
 *
 * @var object
 */
let _formEditorApp = null;

/**
 * @private
 *
 * @return object
 */
function getFormEditorApp() {
	return _formEditorApp;
};

/**
 * @private
 *
 * @return object
 */
function getPublisherSubscriber() {
	return getFormEditorApp().getPublisherSubscriber();
};

/**
 * @private
 *
 * @return object
 */
function getUtility() {
	return getFormEditorApp().getUtility();
};

/**
 * @private
 *
 * @param object
 * @return object
 */
function getHelper() {
	return Helper;
};

/**
 * @private
 *
 * @return object
 */
function getCurrentlySelectedFormElement() {
	return getFormEditorApp().getCurrentlySelectedFormElement();
};

/**
 * @private
 *
 * @param mixed test
 * @param string message
 * @param int messageCode
 * @return void
 */
function assert(test, message, messageCode) {
	return getFormEditorApp().assert(test, message, messageCode);
};

/**
 * @private
 *
 * @return void
 * @throws 1491643380
 */
function _helperSetup() {
	assert('function' === $.type(Helper.bootstrap), 'The view model helper does not implement the method "bootstrap"', 1491643380);
	Helper.bootstrap(getFormEditorApp());
};

/**
 * @private
 *
 * @return void
 */
function _subscribeEvents() {
	/**
	 * @private
	 *
	 * @param string
	 * @param array
	 * args[0] = formElement
	 * args[1] = template
	 * @return void
	 */
	getPublisherSubscriber().subscribe('view/stage/abstract/render/template/perform', function (topic, args) {
		// make sure its captcha.eu plugin
		if (args[0].get('type') === 'Altcha') {
			getFormEditorApp().getViewModel().getStage().renderSimpleTemplateWithValidators(args[0], args[1]);
		}
	});
};

/**
 * @public
 *
 * @param object formEditorApp
 * @return void
 */
export function bootstrap(formEditorApp) {
	_formEditorApp = formEditorApp;
	_helperSetup();
	_subscribeEvents();
};
