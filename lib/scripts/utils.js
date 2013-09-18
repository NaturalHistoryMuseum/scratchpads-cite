/**
 * optionParser
 *
 * Simple option parsing. Expect all options to be preceded by a switch.
 * Return an object on success defined the parsed options, a string
 * on error representing the errors and usage message
 */
exports.optionParser = function(options, arguments) {
  var args = {};
  var errors = [];
  var opt = 0;
  while ((opt <= arguments.length-2) && (arguments[opt][0] == '-')) {
    var sw = arguments[opt].replace(/^-/, '');
    if (typeof options[sw] !== 'undefined') {
      args[sw] = arguments[opt+1].toString();
    } else {
      errors.push("Unknown switch: -" + sw);
    }
    opt = opt + 2;
  }

  for(option in options) {
    var info = options[option];
    if (typeof(args[option]) === 'undefined') {
      if (typeof(info['default']) !== 'undefined') {
        args[option] = info['default'];
      } else if ((typeof(info['required']) !== 'undefined') && info['required'] == true) {
        errors.push("Missing required parameter: -" + option);
      }
    }
  }
  if (errors.length > 0) {
    var output = errors.join("\n");
    for (var index in options) {
      output = output + "-" + index.toString() + ": " + options[index].toString() + "\n";
    }
    throw output;
  } else {
    return args;
  }
}; 

exports.log = function(o) {
  console.log(JSON.stringify(o));
}
