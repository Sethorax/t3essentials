# TYPO3 Extension - ``t3essentials``

#### Introduction
This extension adds quality of life and performance improvements to TYPO3.  

#### What does it do
This extension adds the following features:
- Automatically sets the **canonical url** for the current page
- Automatically sets **hreflang tags** for the curren tpage
- **Concatenates JavaScript** and includes it with the ``defer`` and ``async`` attributes
- Adds support for **critical path CSS** and includes it as inline CSS in the head
- Concatenates CSS and loads it deferred after Load
- Minifies HTML output
- Adds support for **dns-prefetch** links
- Automatically adds dns-prefetch links for JavaScript libraries
- Adds support for **Google Analytics**
- Adds support for **Google Fonts**
- Automatically sorts the html-tags in the head

Every feature is configurable by typoscript.  
If a feature is not needed, it can be easily disabled via typoscript.

You can find detailed information about the typoscript settings in the [Wiki](../../wiki).

#### Dependencies
- Typo3 7.6.X
- PHP 5.5 - 7.0