// Opens the OnlyOffice editor in every element that carries its configuration.
window.addEventListener('load', () => {
  if (typeof window.DocsAPI === 'undefined') {
    return
  }
  document.querySelectorAll('[data-documents-onlyoffice]').forEach((element) => {
    new window.DocsAPI.DocEditor(element.id, JSON.parse(element.dataset.documentsOnlyoffice))
  })
})
