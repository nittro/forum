# Nittro Forums

## Requirements
 - PHP 7.1+
 - PostgreSQL database

## Installation

```bash
composer create-project nittro/forum forum
cd forum
npm install
node_modules/.bin/gulp
cp etc/config.local.dist etc/config.local.neon
$EDITOR etc/config.local.neon
```

You'll need to fill in at least the `doctrine` section;
also if you're not just testing this locally you'll probably
want to set a proper `url` in the `console` section and remove
the `nextras/mail-panel`-related stuff (basically just remove the
whole `tracy` and `services` sections).

Now let's create the database schema and forum admin user:

```bash
bin/console orm:schema-tool:update --force
bin/console user:create
```

Next you'll need to define some categories. The admin part of the forum
isn't ready yet, so you'll need to do this manually. Fire up your
favourite database admin tool and add records to the `categories`
table; you can use the following SQL as a template:

```sql
INSERT INTO "categories" (
    "id",
    "parent_id",
    "position",
    "slug",
    "name",
    "description",
    "description_source"
) VALUES (
    nextval('categories_id_seq'::regclass),
    null, -- parent id
    0, -- 0-based sorting index
    'my-first-category', -- this will be used in the category URL
    'My First Category',
    '<p><em>(insert description)</em></p>', -- html generated from md
    '__(insert description)__' -- md
);
```

And that's it for now :-)
