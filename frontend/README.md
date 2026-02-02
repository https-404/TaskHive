# TaskHive Frontend

## Tech Stack

- **Vue 3** - Progressive JavaScript framework
- **Vite** - Next generation frontend tooling
- **Tailwind CSS** - Utility-first CSS framework
- **Vuex** - State management for Vue.js
- **Axios** - HTTP client for making API requests
- **Vue Router** - Official router for Vue.js

## Getting Started

### Prerequisites

- Node.js >= 16
- npm or yarn

### Installation

Install dependencies:

```bash
npm install
```

### Running the Frontend

Start the development server:

```bash
npm run dev
```

The application will be available at `http://localhost:5173` (or the next available port)

### Building for Production

Build the application for production:

```bash
npm run build
```

Preview the production build:

```bash
npm run preview
```

### Project Structure

```
src/
├── components/    # Reusable Vue components
├── views/         # Page-level components (routes)
├── store/         # Vuex store modules
├── services/      # API service layer (Axios)
├── assets/        # Static assets
├── App.vue        # Root component
├── main.js        # Application entry point
└── style.css      # Global styles (Tailwind directives)
```

### Configuration

- **Tailwind CSS** is configured in `tailwind.config.js`
- **PostCSS** configuration is in `postcss.config.js`
- **Vite** configuration is in `vite.config.js`
