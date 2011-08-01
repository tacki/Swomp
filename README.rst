=====
Swomp
=====

:Authors:
   Markus Schlegel

:Version:
   0.2

-------------------
Readme
-------------------

About:
~~~~~~~~~~~~~~~~~
Swomp is a Manager for static Files like \*.css or \*.js in PHP-Applications. It gives you the possibility
to send all static Files in 1 File for each Type to the Clients Browser. Plus it adds Compression to save
Bandwith.

Usage:
~~~~~~~~~~~~~~~~~

There are 2 ways to use Swomp in your Application:

**Fetcher-Method**
    This is the type of Method used in the demo. The fetcher is is directly called by the Browser like in this
    example::

    <link rel='stylesheet' href='fetch.php/css'>

    The Fetcher is as flexible as you want it to be as it's completly managed by you, the Programmer. The store
    directory doesn't need to be public readable cause everything is done through the Fetcher.

**Store-Method**
    The Store is a Directory managed by Stomp and holding all the 'compiled' Files. Swomp itself finds the
    correct File for you::

    $swomp = new Swomp\Main;
    $swomp->setSourceDirectory('source/css');
    $cssPath = $swomp->getCombinedStorePath('css');

    The Variable $cssPath now holds the correct Path to the Store Element::

    <link rel='stylesheet' href='<?=$cssPath?>'>

    This example result is the same as above. Swomp just points the Client Browser to the correct File to use
    and creates them on-the-fly if they don't exist.

Extending:
~~~~~~~~~~~~~~~~~

If you need more Filters, just write your own Class implementing Swomp\Filters\FilterInterfac and load it
with::

    $swomp->addFilter($myCustomClass, 40);

The second Parameter is a Priority (lower=earlier called). The standard Compression-Filters are called at
Priority 50.

