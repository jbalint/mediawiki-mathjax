//http://docs.mathjax.org/en/v3.0-latest/web/configuration.html#configuration
window.MathJax = {
  tex: {
      inlineMath: [['$', '$']
                   //, ['\\(', '\\)']]
                   ]
  },
  svg: {
    fontCache: 'global'
  }
};

(function () {
  var script = document.createElement('script');
  script.src = 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js';
  script.async = true;
  document.head.appendChild(script);
})();
