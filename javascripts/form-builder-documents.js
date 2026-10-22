// The `documents` field in the form builder: one checkbox per configured service, all ticked on a new field.
const container = document.getElementById('form-builder-container')
let services = {}
try {
  services = JSON.parse(container?.dataset.extensions || '{}')?.documents?.services || {}
} catch {
  services = {}
}

window.formBuilderFields = window.formBuilderFields || {}
window.formBuilderFields.documents = ({ readConf, writeConf }) => ({
  field: {
    label: _t('DOCUMENTS_FORM_BUILDER_LABEL'),
    name: 'documents',
    attrs: { type: 'documents' },
    icon: '<svg class="yw-icon" aria-hidden="true"><use href="src/assets/icons.svg#file-text"/></svg>',
  },
  defaultIdentifier: 'bf_documents',
  attributes: {
    document_types: {
      label: _t('DOCUMENTS_FORM_BUILDER_TYPES'),
      options: services,
      multiple: true,
      value: Object.keys(services).join(','),
      description: _t('DOCUMENTS_FORM_BUILDER_TYPES_HINT'),
    },
    hint: { label: _t('BAZ_FORM_EDIT_HELP'), value: '' },
    read_access: readConf,
    write_access: writeConf,
  },
  disabledAttributes: ['default'],
  advancedAttributes: ['read_access', 'write_access'],
})
