<!DOCTYPE html>

<html>
  <head>
    <title>{$source->name|escape}</title>
    <link rel="stylesheet" href="/style/default.css" />
  </head>
  <body>
    <nav class="breadcrumbs">
      <li><a href="/">Home</a></li>
      <li><a href="/data">Data sources</a></li>
    </nav>

    <main>
      <h1>{$source->name|escape}</h1>

      <section id="datasets">
        <h2>Datasets from {$source->name|escape}</h2>

        <ul>
          {foreach item=dataset from=$datasets}
          <li><a href="/data/{$source->ident|escape:'url'}/{$dataset->ident|escape:'url'}">{$dataset->name|escape}</a></li>
          {/foreach}      
        </ul>

      </section>
    </main>
  </body>
</html>