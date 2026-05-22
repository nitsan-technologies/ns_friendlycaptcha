/*
 * Module: @nitsan/ns-friendlycaptcha/backend/form-editor/view-model.js
 */
import * as Helper from '@typo3/form/backend/form-editor/helper.js';

export function bootstrap(formEditorApp) {
  Helper.bootstrap(formEditorApp);

  formEditorApp.getPublisherSubscriber().subscribe(
    'view/stage/abstract/render/template/perform',
    /**
     * @param {string} topic
     * @param {[FormElement,JQuery]} args
     * @return {void}
     */
    (topic, args) => {
      if (args[0].get('type') === 'Recaptcha') {
        formEditorApp
          .getViewModel()
          .getStage()
          .renderSimpleTemplateWithValidators(args[0], args[1]);
      }
    }
  );
}
