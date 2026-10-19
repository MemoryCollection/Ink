/**
 * 墨 Ink —— 轻量语法着色
 * 不依赖任何第三方库，颜色全部由 CSS 变量控制。
 */
(function (window, document) {
  'use strict';

  var MAX_LENGTH = 60000; // 超长代码直接跳过，避免阻塞

  var KEYWORDS = {
    js: 'async await break case catch class const continue debugger default delete do else export extends finally for from function get if import in instanceof let new null of return set static super switch this throw true false try typeof undefined var void while with yield',
    ts: 'abstract as async await break case catch class const continue declare default delete do else enum export extends false finally for from function get if implements import in instanceof interface keyof let namespace never new null number of private protected public readonly return set static string super switch symbol this throw true try type typeof undefined unknown var void while yield',
    php: 'abstract and array as break callable case catch class clone const continue declare default do echo else elseif empty enddeclare endfor endforeach endif endswitch endwhile extends false final finally fn for foreach function global goto if implements include include_once instanceof insteadof interface isset list match namespace new null or print private protected public readonly require require_once return static switch throw trait true try unset use var while xor yield',
    java: 'abstract assert boolean break byte case catch char class const continue default do double else enum extends false final finally float for goto if implements import instanceof int interface long native new null package private protected public return short static strictfp super switch synchronized this throw throws transient true try void volatile while var record',
    kotlin: 'abstract actual annotation as break by catch class companion const constructor continue crossinline data delegate do dynamic else enum expect external false field file final finally for fun get if import in infix init inline inner interface internal is lateinit noinline null object open operator out override package param private protected public reified return sealed set super suspend tailrec this throw true try typealias typeof val value var vararg when where while',
    python: 'and as assert async await break class continue def del elif else except False finally for from global if import in is lambda None nonlocal not or pass raise return True try while with yield self print',
    go: 'break case chan const continue default defer else fallthrough for func go goto if import interface map package range return select struct switch type var nil true false make new len cap append string int int64 float64 bool byte rune error',
    sql: 'select from where insert into values update set delete create table alter drop index join left right inner outer full on group by order having limit offset as and or not null is in like between distinct count sum avg min max case when then else end union all exists primary key default',
    bash: 'if then else elif fi for do done while until case esac function echo export local return exit source alias unset readonly declare in',
    json: 'true false null',
    yaml: 'true false null yes no on off',
    css: '',
    html: ''
  };

  var ALIAS = {
    javascript: 'js', jsx: 'js', mjs: 'js', cjs: 'js',
    typescript: 'ts', tsx: 'ts',
    'c++': 'java', csharp: 'java', cs: 'java', c: 'java',
    kt: 'kotlin', kts: 'kotlin',
    py: 'python', python3: 'python',
    golang: 'go',
    mysql: 'sql', postgresql: 'sql', pgsql: 'sql', sqlite: 'sql',
    sh: 'bash', shell: 'bash', zsh: 'bash', console: 'bash',
    yml: 'yaml',
    xml: 'html', vue: 'html', svg: 'html',
    scss: 'css', less: 'css',
    php3: 'php', php4: 'php', php5: 'php'
  };

  function escapeHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function wrap(cls, text) {
    return '<span class="' + cls + '">' + escapeHtml(text) + '</span>';
  }

  function kwPattern(words) {
    var list = words.split(/\s+/).filter(Boolean);
    if (!list.length) {
      return null;
    }
    return new RegExp('\\b(?:' + list.join('|') + ')\\b', 'y');
  }

  var STRING_RE = /("""[\s\S]*?"""|'''[\s\S]*?'''|"(?:\\.|[^"\\\n])*"|'(?:\\.|[^'\\\n])*'|`(?:\\.|[^`\\])*`)/y;
  var NUMBER_RE = /\b(?:0[xX][\da-fA-F]+|0[bB][01]+|\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)\b/y;
  var FN_RE = /\b[A-Za-z_$][\w$]*(?=\s*\()/y;

  function buildRules(lang) {
    var rules = [];
    var words = KEYWORDS[lang] || '';
    var hashComment = (lang === 'python' || lang === 'bash' || lang === 'yaml');

    if (hashComment) {
      rules.push({ cls: 'tok-com', re: /#[^\n]*/y });
    } else if (lang === 'sql') {
      rules.push({ cls: 'tok-com', re: /--[^\n]*|\/\*[\s\S]*?\*\//y });
    } else {
      rules.push({ cls: 'tok-com', re: /\/\/[^\n]*|\/\*[\s\S]*?\*\//y });
    }

    rules.push({ cls: 'tok-str', re: STRING_RE });
    rules.push({ cls: 'tok-num', re: NUMBER_RE });

    var kw = kwPattern(words);
    if (kw) {
      rules.push({ cls: 'tok-key', re: kw });
    }

    if (!hashComment) {
      rules.push({ cls: 'tok-fn', re: FN_RE });
    }

    rules.push({ cls: 'tok-op', re: /[+\-*/%=<>!&|^~?:;,.()\[\]{}]+/y });

    return rules;
  }

  function tokenize(code, rules) {
    var out = '';
    var buf = '';
    var i = 0;
    var len = code.length;

    while (i < len) {
      var hit = false;

      for (var r = 0; r < rules.length; r++) {
        var re = rules[r].re;
        re.lastIndex = i;
        var m = re.exec(code);
        if (m && m[0].length) {
          if (buf) {
            out += escapeHtml(buf);
            buf = '';
          }
          out += wrap(rules[r].cls, m[0]);
          i += m[0].length;
          hit = true;
          break;
        }
      }

      if (!hit) {
        buf += code[i];
        i++;
      }
    }

    if (buf) {
      out += escapeHtml(buf);
    }

    return out;
  }

  function highlightHtml(code) {
    var parts = [];
    var re = /(<!--[\s\S]*?-->)|(<\/?)([\w:-]+)|([\w:-]+)(?=\s*=)|("(?:[^"]*)"|'(?:[^']*)')/g;
    var last = 0;
    var m;

    while ((m = re.exec(code)) !== null) {
      if (m.index > last) {
        parts.push(escapeHtml(code.slice(last, m.index)));
      }
      if (m[1]) {
        parts.push(wrap('tok-com', m[1]));
      } else if (m[3]) {
        parts.push(wrap('tok-op', m[2] || '') + wrap('tok-tag', m[3]));
      } else if (m[4]) {
        parts.push(wrap('tok-attr', m[4]));
      } else if (m[5]) {
        parts.push(wrap('tok-str', m[5]));
      }
      last = re.lastIndex;
    }

    if (last < code.length) {
      parts.push(escapeHtml(code.slice(last)));
    }

    return parts.join('');
  }

  function highlightCss(code) {
    var parts = [];
    var re = /(\/\*[\s\S]*?\*\/)|([.#][\w-]+|@[\w-]+)|([\w-]+)(?=\s*:)|(:\s*[^;{}]+)/g;
    var last = 0;
    var m;

    while ((m = re.exec(code)) !== null) {
      if (m.index > last) {
        parts.push(escapeHtml(code.slice(last, m.index)));
      }
      if (m[1]) {
        parts.push(wrap('tok-com', m[1]));
      } else if (m[2]) {
        parts.push(wrap('tok-tag', m[2]));
      } else if (m[3]) {
        parts.push(wrap('tok-attr', m[3]));
      } else if (m[4]) {
        parts.push(wrap('tok-str', m[4]));
      }
      last = re.lastIndex;
    }

    if (last < code.length) {
      parts.push(escapeHtml(code.slice(last)));
    }

    return parts.join('');
  }

  function detectLang(codeEl, preEl) {
    var cls = (codeEl.className || '') + ' ' + (preEl.className || '');
    var m = cls.match(/(?:language|lang)-([\w+#.-]+)/i);
    if (!m) {
      return '';
    }
    var lang = m[1].toLowerCase();
    return ALIAS[lang] || lang;
  }

  function render(codeEl, lang) {
    var raw = codeEl.textContent;
    if (!raw.trim()) {
      return;
    }

    if (!lang || lang === 'text' || lang === 'plain' || lang === 'plaintext' || lang === 'none') {
      return;
    }

    if (lang === 'html') {
      codeEl.innerHTML = highlightHtml(raw);
    } else if (lang === 'css') {
      codeEl.innerHTML = highlightCss(raw);
    } else {
      var rules = buildRules(lang);
      codeEl.innerHTML = tokenize(raw, rules);
    }
  }

  function init() {
    var pres = document.querySelectorAll('.content pre');

    Array.prototype.forEach.call(pres, function (pre) {
      var code = pre.querySelector('code');
      if (!code) {
        return;
      }

      // 已由其它高亮插件处理过的区块不重复处理
      if (pre.classList.contains('hljs') || code.classList.contains('hljs')) {
        return;
      }
      if (code.getAttribute('data-no-highlight') !== null) {
        return;
      }

      var lang = detectLang(code, pre);

      if (code.textContent.length > MAX_LENGTH) {
        return;
      }

      try {
        render(code, lang || 'text');
      } catch (e) {
        // 高亮失败时保持原文，不影响阅读
      }
    });
  }

  window.InkHighlight = { init: init };
})(window, document);
