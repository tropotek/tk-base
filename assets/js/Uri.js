/**
 * Uri Object
 *
 * The object breaks down a URL into its components and allows you to
 * modify/query/redirect a URL with ease. It is based on Tropotek's PHP TkLib
 * URL object.
 *
 * Examples:
 *
 * var url = new Uri(); // create a url object with the current request
 * (window.location) var url = new url('http://www.domain.com');
 * url.addField('param1', 'value1').addField('param2', 'value2');
 * alert(url.getField('param2')); url.redirect();
 *
 * If you want to contribute further to this class visit our site.
 *
 * @site: http://www.domtemplate.com/
 * @author Michael Mifsud
 * @package Uri
 */

function Uri()
{

  // Class Variables
  this.source = '';

  this.scheme = 'http';
  this.username = '';
  this.password = '';
  this.host = '';
  this.port = '80';
  this.path = '';
  this.fragment = '';
  this.query = null;

  // Constructor
  this.constructor = function()
  {
    this.source = window.location.toString();
    if (arguments[0]) {
      this.source = arguments[0];
      if (this.source.charAt(0) == '/') {
        this.source = window.location.protocol + '//' + window.location.hostname + this.source;
      }
    }

    // init class
    var data = parseUri(this.source);
    this.scheme = data.protocol;
    this.username = data.user;
    this.password = data.password;
    this.host = data.host;
    if (data.port) {
      this.port = data.port;
    }
    this.path = data.path;
    this.fragment = data.anchor;

    this.query = data.queryKey;
    if (!this.query) {
      this.query = {};
    }
  };

  this.vd = function(obj)
  {
    if (console) {
      console.log(obj);
    }
  };

  // Class Methods
  this.setFragment = function(str)
  {
    this.fragment = str;
    return this;
  };

  this.getFragment = function()
  {
    return this.fragment;
  };

  this.setHost = function(str)
  {
    this.host = str;
    return this;
  };

  this.getHost = function()
  {
    return this.host;
  };

  this.setUsername = function(str)
  {
    this.username = str;
    return this;
  };

  this.getUsername = function()
  {
    return this.username;
  };

  this.setPassword = function(str)
  {
    this.password = str;
    return this;
  };

  this.getPassword = function()
  {
    return this.password;
  };

  this.setPath = function(str)
  {
    this.path = str;
    return this;
  };

  this.getPath = function()
  {
    return this.path;
  };

  this.setPort = function(str)
  {
    this.port = str;
    return this;
  };

  this.getPort = function()
  {
    return this.port;
  };

  this.setScheme = function(str)
  {
    this.scheme = str;
    return this;
  };

  this.getScheme = function()
  {
    return this.scheme;
  };

  this.exists = function(name)
  {
    if (name in this.query) {
      return true;
    }
    return false;
  };

  this.getField = function(name)
  {
    if (this.exists(name)) {
      return this.query[name];
    }
    return '';
  };

  this.get = function(name)
  {
    return this.getField(name);
  };

  this.setField = function(name, value)
  {
    this.query[name] = value;
    return this;
  };

  this.set = function(name, value)
  {
    return this.setField(name, value);
  };

  this.deleteField = function(name)
  {
    delete this.query[name];
    return this;
  };

  this.clearFields = function()
  {
    this.query = {};
    return this;
  };

  this.getBasename = function()
  {
    var pos = this.path.lastIndexOf('/');
    if (pos > -1) {
      return this.path.substring(pos + 1);
    }
    pos = this.path.lastIndexOf('\\');
    if (pos > -1) {
      return this.path.substring(pos + 1);
    }
    return this.path;
  };

  this.getDirname = function()
  {
    var pos = this.path.lastIndexOf('/');
    if (pos > -1) {
      if (this.path.substring(0, pos)) {
        return this.path.substring(0, pos);
      }
      return '/';
    }
    pos = this.path.lastIndexOf('\\');
    if (pos > -1) {
      if (this.path.substring(0, pos)) {
        return this.path.substring(0, pos);
      }
      return '/';
    }
    return '/';
  };

  this.getExtension = function()
  {
    if (this.path.substring(-6) == 'tar.gz') {
      return 'tar.gz';
    }
    var pos = this.path.lastIndexOf('.');
    if (pos > -1) {
      return this.path.substring(pos + 1);
    }
    return '';
  };

  this.redirect = function()
  {
    window.location = this.toString();
  };

  this.toString = function()
  {
    var username = '';
    if (this.username) {
      username = this.username;
      if (this.password) {
        username += this.password;
      }
      username += '@';
    }
    var port = '';
    if (this.port != '80') {
      port = ':' + this.port;
    }
    var path = '/';
    if (this.path) {
      path = this.path;
    }

    var query = '';
    for (var k in this.query) {
      if (this.query[k] === '') { continue; }
      query += k + '=' + escape(this.query[k]) + '&';
    }
    if (query) {
      query = '?' + query.substring(0, query.length-1);
    }

    var fragment = '';
    if (this.fragment) {
      fragment = '#' + this.fragment;
    }
    return this.scheme + '://' + username + this.host + port + path + query + fragment;
  };

  // Call constructor
  if (arguments[0])
    this.constructor(arguments[0]);
  else
    this.constructor();
}

// ////////////////////////////////////////////////////
// parseUri 1.2.2
// (c) Steven Levithan <stevenlevithan.com>
// MIT License
// http://stevenlevithan.com/demo/parseuri
// ////////////////////////////////////////////////////
function parseUri(str)
{
  var o = parseUri.options, m = o.parser[o.strictMode ? "strict" : "loose"].exec(str), uri = {}, i = 14;
  while (i--)
    uri[o.key[i]] = m[i] || "";
  uri[o.q.name] = {};
  uri[o.key[12]].replace(o.q.parser, function($0, $1, $2)
  {
    if ($1)
      uri[o.q.name][$1] = $2;
  });
  return uri;
};
parseUri.options = {
  strictMode : false,
  key : [ "source", "protocol", "authority", "userInfo", "user", "password", "host", "port", "relative", "path", "directory", "file", "query", "anchor" ],
  q : {
    name : "queryKey",
    parser : /(?:^|&)([^&=]*)=?([^&]*)/g
  },
  parser : {
    strict : /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
    loose : /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
  }
};
