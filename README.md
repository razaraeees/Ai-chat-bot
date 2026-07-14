# Nexus AI

Nexus AI is a web-based AI chat application. Users can create an account, start chats, return to saved conversations, and manage teams.

The app uses OpenRouter for AI responses through Prism PHP.

## Features

- User registration, login, password reset, email verification, and two-factor security settings.
- AI chat with a clean Vue interface.
- Automatic conversation creation and saved chat messages.
- Conversation history is loaded when a user opens a saved chat.
- Each AI request includes the latest 10 messages from that conversation.
- Team creation, member roles, team switching, and email invitations.
- Light and dark appearance settings.
- Feature tests for chat, authentication, settings, and teams.

## Tech stack

- PHP 8.3 and Laravel 13
- Vue 3, TypeScript, Inertia.js, and Vite
- Tailwind CSS 4
- SQLite by default (other Laravel-supported databases can also be used)
- Laravel Fortify for authentication
- Prism PHP and OpenRouter for AI requests

## Requirements

Install these tools before starting:

- PHP 8.3 or newer
- Composer
- Node.js and npm
- An OpenRouter API key

## Installation

1. Install PHP packages:

   ```bash
   composer install
   ```

2. Create the environment file:

   ```bash
   cp .env.example .env
   ```

   On Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Generate the application key:

   ```bash
   php artisan key:generate
   ```

4. Add your OpenRouter API key to `.env`:

   ```env
   OPENROUTER_API_KEY=your_openrouter_api_key
   ```

5. Create the SQLite database file if it does not already exist:

   ```powershell
   New-Item -ItemType File -Path database/database.sqlite -Force
   ```

6. Run the database migrations:

   ```bash
   php artisan migrate
   ```

7. Install frontend packages and build the assets:

   ```bash
   npm install
   npm run build
   ```

## Running the app

For local development, use two terminals.

```bash
php artisan serve
```

```bash
npm run dev
```

Then open the URL shown by Laravel, usually `http://127.0.0.1:8000`.

You can also use the project helper command:

```bash
composer dev
```

## AI configuration

The AI model, system prompt, and response token limit are set in [config/ai.php](config/ai.php).

The default model is `poolside/laguna-xs-2.1:free`. To use a different OpenRouter model, update the `model` value in that file. Provider settings, including `OPENROUTER_API_KEY`, are in [config/prism.php](config/prism.php).

## How chat works

1. A signed-in and verified user sends a message.
2. The app creates a conversation when one is not already selected.
3. The user message is saved in the database.
4. The latest 10 messages are sent to the AI provider with the system prompt.
5. The AI reply is saved and returned to the chat screen.

Users can only view conversations that they own.

## Useful commands

```bash
# Run all PHP tests
php artisan test

# Run code-style checks
composer lint:check

# Run PHP static analysis
composer types:check

# Check frontend formatting, linting, and TypeScript
npm run format:check
npm run lint:check
npm run types:check
```

## Main project folders

| Folder | Purpose |
| --- | --- |
| `app/Http/Controllers` | Handles chat, dashboard, settings, and team requests. |
| `app/Services/AiChat` | Creates conversations, saves messages, prepares history, and calls the AI provider. |
| `app/Models` | Database models such as users, conversations, messages, teams, and invitations. |
| `resources/js` | Vue pages, layouts, components, and chat composables. |
| `database/migrations` | Database table definitions. |
| `tests` | Automated feature and unit tests. |

## Important notes

- Do not commit `.env` because it contains private keys and local settings.
- The default `.env.example` uses SQLite, database sessions, database cache, and database queues. Run migrations before using the app.
- Email delivery uses Laravel's configured mail driver. The default local setting writes mail to logs.

## License

This project uses the MIT license, as defined in `composer.json`.
