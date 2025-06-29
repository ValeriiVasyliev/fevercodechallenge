# 🚀 FeverCodeChallenge

## 📑 Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
    * [Install Docker](#1-install-docker)
    * [Install DDEV](#2-install-ddev)
    * [Install Composer](#3-install-composer)
    * [Install WP-CLI](#4-install-wp-cli)
    * [Clone & Install the Project](#5-clone--install-the-project)
    * [Final Steps](#final-steps)
* [Plugin Structure](#plugin-structure-fever-code-challenge)
* [Developer Tools & Testing](#developer-tools--testing)
  * [Available Composer Scripts](#available-composer-scripts)
  * [How to Use](#how-to-use)
* [Tasks Answers](#tasks-answers)

---

## ✅ Requirements

Ensure the following tools are installed on your system before starting the project:

| Requirement       | Check Command        | Installation Link                                                                      |
| ----------------- | -------------------- | -------------------------------------------------------------------------------------- |
| PHP >= 8.3        | `php -v`             | [php.net](https://www.php.net/manual/en/install.php)                                   |
| Composer >= 2.1.6 | `composer --version` | [getcomposer.org](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) |
| WP-CLI >= 2.4.0   | `wp --info`          | See instructions below                                                                 |
| DDEV              | `ddev --version`     | See instructions below                                                                 |
| Docker            | -                    | [Docker Desktop](https://www.docker.com/products/docker-desktop)                       |

---

## ⚙️ Installation

### 1. 🐳 Install Docker

Docker is required for running DDEV containers.

* [Download Docker Desktop](https://www.docker.com/products/docker-desktop)
* After installation, launch Docker to keep it running in the background.

---

### 2. 🧰 Install DDEV

DDEV simplifies managing WordPress development environments.

#### macOS:

```bash
brew install drud/ddev/ddev
```

#### Windows (via Chocolatey):

```powershell
choco install ddev
```

More options: [https://ddev.readthedocs.io/en/stable/#installation](https://ddev.readthedocs.io/en/stable/#installation)

---

### 3. 🎼 Install Composer

Composer manages PHP dependencies, including WordPress core and plugins.

#### macOS:

```bash
brew install composer
```

#### Ubuntu/Debian:

```bash
sudo apt install composer
```

Or download from the official site:
👉 [https://getcomposer.org/download](https://getcomposer.org/download)

---

### 4. 🧪 Install WP-CLI

WP-CLI is a command-line tool for managing WordPress.

```bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
```

Verify it:

```bash
wp --info
```

---

### 5. 📦 Clone & Install the Project

Once your environment is ready, clone and install the project:

```bash
git clone git@github.com:FeverCodeChallenge/Valerii.Vasyliev.git
cd Valerii.Vasyliev
composer build
```

This will automatically install:

* WordPress core
* Required plugins and themes
* Project-specific files

---

### ✅ Final Steps

1. Start the DDEV container:

   ```bash
   ddev start
   ```

2. Access your site:

   ```
   https://<project-name>.ddev.site
   ```

   Replace `<project-name>` with your actual DDEV project name (usually the folder name).

---

## 🔌 Plugin Structure: `fever-code-challenge`

```
wp-content/plugins/fever-code-challenge/
├── assets/                            # → Front-end assets: JS, CSS, TS, Images.
│   ├── css/
│   │   └── style.css                  # → Main stylesheet for the plugin.
│   ├── img/                           # → Image assets (used in frontend templates).
│   ├── js/
│   │   ├── front-pokemon.js
│   │   ├── front-pokemon-generate.js
│   │   └── front-pokemon-random.js   # → Compiled JavaScript files for front-end features.
│   └── ts/
│       └── front-pokemon-list.ts     # → TypeScript source file (likely compiled into js/).
│
├── languages/
│   └── fever-code-challenge.pot      # → POT file for translations (localization-ready).
│
├── src/                               # → Main PHP source code.
│   ├── API/
│   │   └── PokeAPI.php               # → External API integration class (Pokémon API).
│   ├── Front/
│   │   ├── Pokemon.php               # → Front-end logic for Pokémon details
│   │   ├── PokemonGenerate.php       # → Front-end logicfor generating Pokémon. 
│   │   ├── PokemonList.php           # → Front-end logic for listing Pokémon.
│   │   └── PokemonRandom.php         # → Front-end logic for random Pokémon.
│   ├── Interfaces/
│   │   └── IAPI.php                  # → Interface for API abstraction.
│   ├── Admin.php                     # → Admin panel related logic.
│   ├── Front.php                     # → Entry point to frontend functionality.
│   ├── Plugin.php                    # → Main plugin class: handles setup, hooks, init.
│   └── REST.php                      # → Registers REST API routes and controllers.
│
├── templates/                         # → Output templates for views.
│   ├── generate-pokemon.php           # → Template for generating Pokémon.
│   ├── pokemon-list.php               # → Template for listing Pokémon.
│   └── random-pokemon.php             # → Template for displaying a random Pokémon.
│
├── tests/                             # → Unit and integration tests (PHPUnit-based).
│
├── composer.json                      # → Composer config: autoloading, PHP deps.
├── package.json                       # → JavaScript/TypeScript dependencies.
├── patchwork.json                     # → Config for Patchwork (mocking/stubbing in tests).
├── phpcs.xml                          # → PHP CodeSniffer config (coding standards).
├── phpunit.xml.dist                   # → PHPUnit test configuration file.
├── plugin.php                         # → Plugin entry file with headers.
├── tsconfig.json                      # → TypeScript configuration.
└── webpack.config.js                  # → Webpack build config for compiling assets.
```

Got it! Here's the updated section with each command prefixed by `cd wp-content/plugins/fever-code-challenge &&` for direct execution:

---

## 🧪 Developer Tools & Testing

The `fever-code-challenge` plugin includes a set of developer tools to ensure code quality, consistency, and test coverage. These are configured in `composer.json` and can be executed using Composer scripts.

### 🧰 Available Composer Scripts

| Script             | Description                                                               |
| ------------------ | ------------------------------------------------------------------------- |
| `phpcs:config-set` | Sets the paths for coding standard rulesets (`WPCS`, `PHPCompatibility`). |
| `phpcs`            | Runs code linting using the rules defined in `phpcs.xml`.                 |
| `phpcbf`           | Automatically fixes style issues based on coding standards.               |
| `test`             | Executes PHPUnit tests with the bootstrap file.                           |
| `pot`              | Generates a `.pot` file for translations using WP-CLI's i18n command.     |

---

### ✅ How to Use

Run the following commands from anywhere in your terminal:

#### 📌 Set PHP CodeSniffer Paths

```bash
cd wp-content/plugins/fever-code-challenge && composer run phpcs:config-set
```

This configures PHPCS to recognize WordPress coding standards and PHP compatibility rules.

#### 🧹 Run Code Style Check

```bash
cd wp-content/plugins/fever-code-challenge && composer run phpcs
```

Scans PHP files for style violations using rules in `phpcs.xml`.

#### ✨ Auto-fix Style Issues

```bash
cd wp-content/plugins/fever-code-challenge && composer run phpcbf
```

Automatically fixes fixable style violations.

#### 🧪 Run Tests

```bash
cd wp-content/plugins/fever-code-challenge && composer run test
```

Runs PHPUnit tests defined in the `tests/` directory using `phpunit.xml.dist`.

Make sure you have a test environment configured (e.g., with DDEV and `tests/bootstrap.php`).

#### 🌐 Generate Translation File

```bash
cd wp-content/plugins/fever-code-challenge && composer run pot
```

## Task Answers

> 1. Create a custom post type called “Pokémon” whose slug should be “pokemon”.

**Answer:**  
The new post type "pokemon" is available at the URL:  
https://your-site-url/wp-admin/edit.php?post_type=pokemon

---

> 2. This post type must contain the following properties:  
> a. Photo of the pokemon  
> b. pokemon name  
> c. pokemon description  
> d. primary and secondary type of pokemon  
> e. pokemon weight  
> f. Pokedex number in older version of the game (you can find this info in the api)  
> g. Pokedex number in the most recent version of the game (you can find this info in the api)  
> h. (Optional) The attacks of said pokémon with its short description (in English). Said attacks must be stored as desired, considering efficiency and possible reuse.

**Answer:**  
All these properties have been implemented as post meta fields associated with the "pokemon" post type.

---

> 3. Generate 3 pokemon manually with the data requested in point 2 using the PokéAPI.

**Answer:**  
I've added a button in the admin panel to generate Pokémon manually. You can access it here:  
https://your-site-url/wp-admin/edit.php?post_type=pokemon

---

> 4. Create a template for the custom post type "pokemon" and display:  
> a. Photo of the pokemon  
> b. pokemon name  
> c. pokemon description  
> d. Pokémon types (primary and secondary)  
> e. Number of the pokedex in the most recent version and the name of the game  
> f. Button in which, when clicked, an AJAX call is made to WordPress to show the number of the pokédex of said pokémon in the oldest version of the game with the name of said version.  
> g. (optional) Table of movements of the pokémon with two columns:  
> i. movement name  
> ii. movement description

**Answer:**  
The Pokémon details page is available at:  
https://your-site-url/pokemon/{pokemon-name}/  
It includes all requested information and AJAX functionality.

---

> 5. (Optional) Create a pokémon filter (TypeScript): Initially, this page will show a grid with the photos of the pokémon stored in the database. The user must be able to filter by type (the first 5 types returned by PokéAPI). When a filter is selected it should hide the photos of the pokemon whose first or second type does not match the selected filter. Limit to 6 pokemon per page.

**Answer:**  
The filter page is available at:  
https://your-site-url/pokemon-list/  
It supports filtering by type and pagination with 6 Pokémon per page.

---

> 6. (Optional) Create a custom url (for example http:/localhost/random) that shows a random pokémon stored in the database. Said URL must redirect to the permanent link of the returned pokémon.

**Answer:**  
The random Pokémon is available at:   
https://your-site-url/random/

---

> 7. (Optional) Create a custom url (for example http://localhost:generate) that when summoned will spawn a random pokemon by calling the PokéAPI. It can only be invoked by users who have post creation permissions or higher. This generated pokemon must be stored in WordPress as if it were a manually created post with the same data specified in point 2.

**Answer:**  
This generate URL is available at:  
https://your-site-url/generate/  
It requires user permissions for post creation.

---

> 8. (Optional) Using the WordPress REST API, generate an endpoint to list stored pokémon showing as ID the pokédex number in the most recent version of the game. Generate another endpoint to consult the data of the pokemon requested in point 2 in JSON format.

**Answer:**  
Developed custom REST API endpoints:  
- `/wp-json/fever-code-challenge/v1/pokemon` — lists Pokémon with IDs as recent Pokedex numbers  
- `/wp-json/fever-code-challenge/v1/pokemon1/{id}` — returns detailed JSON data for a Pokémon

---

> 9. (Optional) Would it be possible to implement DAPI (or other similar APIs) in the developed solution? If so, what changes and abstractions would you propose in the different layers of the application to facilitate said integration? (the implementation is optional)

**Answer:**  
Yes, it is possible to implement DAPI or other similar APIs by introducing an abstraction layer and using dependency injection to swap API implementations easily.

For example, in the main plugin file `wp-content/plugins/fever-code-challenge/src/Plugin.php`, the constructor depends on an API interface `IAPI`:

```php
/**
 * Plugin constructor.
 *
 * @param IAPI   $api              Injected API instance.
 * @param string $plugin_file_path Path to main plugin file (__FILE__).
 */
public function __construct(IAPI $api, string $plugin_file_path) {
    $this->api        = $api;
    $this->plugin_url = untrailingslashit(plugin_dir_url($plugin_file_path));
    $this->plugin_dir = dirname($plugin_file_path);
}
````

To support another API in the future, you would:

1. Create a new class implementing the same `IAPI` interface, e.g., `DapiAPI`.

2. Initialize the plugin with the desired API implementation by changing the plugin initialization hook from:

```php
add_action('plugins_loaded', array(new Plugin(new API\PokeAPI(), __FILE__), 'init'));
```

to

```php
add_action('plugins_loaded', array(new Plugin(new API\DapiAPI(), __FILE__), 'init'));
```

This approach leverages **dependency injection**, allowing flexible swapping of API clients without modifying the core plugin logic.

---

> 10. (Optional) The instance becomes more and more popular and starts receiving a lot of traffic generating heavy DB usage. What would you do in this situation?

**Answer:**
To handle increased traffic and DB load:

* Implement caching (object cache, page cache, REST API cache)
* Use a CDN for assets and images
* Optimize database queries and add indexes
* Offload heavy tasks to background jobs or queues
* Scale infrastructure horizontally (load balancing, read replicas)
* Consider DB engine upgrades or optimization

---