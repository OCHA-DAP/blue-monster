<!DOCTYPE html>

<html>
  <head>
    <title>{$dataset->name|escape}</title>
    <link rel="stylesheet" href="/style/default.css" />
  </head>
  <body>
    <nav class="breadcrumbs">
      <li><a href="/">Home</a></li>
      <li><a href="/data">Data sources</a></li>
      <li><a href="/data/{$dataset->source_ident|escape:'url'}">{$dataset->source_name|escape}</a></li>
    </nav>

    <main>
      <h1>{$dataset->name|escape}</h1>

      <form method="GET" action="/search">
        <input type="hidden" name="source" value="{$dataset->source_ident|escape}" />
        <input type="hidden" name="dataset" value="{$dataset->ident|escape}" />
        <input placeholder="Search within {$dataset->name|escape}" />
        <input type="submit" />
      </form>

      <section id="imports">
        <h2>Imports</h2>

        <ul>
          {foreach item=import from=$imports}
          <li><a href="/data/{$dataset->source_ident|escape:'url'}/{$dataset->ident|escape:'url'}/{$import->stamp|escape:'url'}">{$import->stamp|escape}</a></li>
          {/foreach}      
        </ul>

      </section>
    </main>
  </body>
</html>