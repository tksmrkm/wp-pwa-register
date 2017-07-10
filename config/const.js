const path = require('path');
const root = path.resolve(__dirname + '/../');

const constants = {
    root: root,
    output: path.resolve(root + '/templates'),
    context: path.resolve(root + '/assets/js')
};

module.exports = constants;