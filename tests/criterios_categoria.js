const fs = require('node:fs');
const assert = require('node:assert/strict');

const source = fs.readFileSync('views/admin/gestion-academica.php', 'utf8');
const match = source.match(/function refreshVersionDependents\(versionSelect\)\{[\s\S]*?\nfunction table\(/);
assert.ok(match, 'Debe existir la función que carga los selectores dependientes');
const implementation = match[0].replace(/\nfunction table\($/, '');
const labelMap = { categories: row => `${row.codigo} — ${row.nombre}`, criteria: row => row.nombre, subcriteria: row => row.nombre, sources: row => row.nombre_documento };
const esc = value => String(value ?? '').replace(/[&<>"']/g, '');
const data = { categories: [
  { id_categoria: '11', id_version: '1', codigo: 'CAT-1', nombre: 'Investigación' },
  { id_categoria: '12', id_version: '2', codigo: 'CAT-2', nombre: 'Docencia' },
] };
const categorySelect = { value: '', innerHTML: '' };
const form = { querySelector: selector => selector.includes('categories') ? categorySelect : null };
const versionSelect = { value: '1', form };
const run = new Function('acaData', 'esc', 'labelMap', 'versionSelect', `${implementation};refreshVersionDependents(versionSelect);return versionSelect.form.querySelector('[data-options="categories"]').innerHTML;`);
let html = run(data, esc, labelMap, versionSelect);
assert.match(html, /CAT-1.*Investigación/);
assert.doesNotMatch(html, /CAT-2|Docencia/);
console.log('OK: el selector carga únicamente las categorías de la versión seleccionada.');

versionSelect.value = '';
html = run(data, esc, labelMap, versionSelect);
assert.match(html, /Selecciona primero una version borrador/);
console.log('OK: el selector explica que se debe elegir una versión borrador.');
