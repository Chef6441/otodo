(function(){
  function capLines(text, prefixes){
    return text.split(/\n/).map(function(line){
      var leadingMatch = line.match(/^\s*/);
      var leading = leadingMatch ? leadingMatch[0] : '';
      var rest = line.slice(leading.length);
      var prefix = '';
      for (var i=0;i<prefixes.length;i++){
        var p = prefixes[i];
        if (p && rest.startsWith(p)){
          prefix = p;
          rest = rest.slice(p.length);
          break;
        }
      }
      rest = rest.replace(/^(\s*)(\S)/, function(m, ws, ch){ return ws + ch.toUpperCase(); });
      return leading + prefix + rest;
    }).join('\n');
  }
  window.setupAutoCapitalize = function(el, prefixes){
    if (!el) return;
    el.addEventListener('input', function(){
      var pos = el.selectionStart;
      var val = capLines(el.value, prefixes);
      if (val !== el.value){
        el.value = val;
        el.setSelectionRange(pos, pos);
      }
    });
  };
})();
