const path = require('path');
const fs = require('fs-extra');

const deleteFolderRecursive = function(path) {
  if (fs.existsSync(path)) {
    fs.readdirSync(path).forEach(function(file) {
      var curPath = path + "/" + file;
      if (fs.lstatSync(curPath).isDirectory()) { // recurse
        deleteFolderRecursive(curPath);
      } else { // delete file
        fs.unlinkSync(curPath);
      }
    });
    fs.rmdirSync(path);
  }
};

const root = path.resolve(__dirname, '..');

function builder(prefix, svgsPath, componentsPath) {

  const ComponentsSource = (...p) => path.resolve(root, componentsPath, ...p);

// clean output
  try {
    deleteFolderRecursive(ComponentsSource())
  } catch (e) {
  }

// built SVGs to vue components
  const camelcase = require('camelcase');
  const SVGsSource = (...p) => path.resolve(root, svgsPath, ...p);

  if (!fs.existsSync(ComponentsSource())) {
    fs.mkdirSync(ComponentsSource());
  }

  let index = [];

  fs.readdirSync(SVGsSource())
    .map(p => SVGsSource(p))
    .forEach(file => {
      const basename = camelcase(path.basename(file, 'svg'), {pascalCase: true});
      const fullname = `${prefix}${basename}`;

      const output = name => ComponentsSource(name);

      // write vue component
      fs.writeFileSync(output(fullname + '.vue'), `<template>
${fs.readFileSync(file)}
</template>
      `);

      index.push({
        handle: path.basename(file, '.svg'),
        name: fullname
      });
    });

  // write index file
  fs.writeFileSync(
    ComponentsSource('index.js'),
    index.map(item => `import ${item.name} from './${item.name}.vue'`).join("\r\n") + "\r\n\r\n" +
    'export default {' + "\r\n" +
    index.map(item => `    '${item.handle}': ${item.name},`).join("\r\n")
    + "\r\n" + "}"
  );
}

builder('', 'icons/outline', 'src/common/ui/icons/outline');
builder('', 'icons/solid', 'src/common/ui/icons/solid');