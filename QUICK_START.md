# 🚀 UniGPT Quick Start Guide

## Phase 1: ✅ COMPLETED

### What's Been Done

1. **✅ Complete DDD Directory Structure**
   - Domain layer for business logic
   - Infrastructure layer for external services
   - Application services layer
   - HTTP layer with role-based controllers

2. **✅ Vue 3 + Vite Integration**
   - Vue 3.5.26 installed and configured
   - Vite 6.0.11 with hot module replacement
   - Component auto-loading setup

3. **✅ Tailwind CSS + Headless UI**
   - Tailwind CSS 3.4.13 configured
   - Headless UI for accessible components
   - Heroicons for SVG icons
   - Dark mode support

4. **✅ Configuration Files**
   - `config/ai.php` - Multi-AI provider config
   - `config/rag.php` - RAG system settings
   - `config/vector.php` - Vector DB configuration
   - `config/permissions.php` - Role-based permissions

5. **✅ Enums for Type Safety**
   - UserRole, DocumentStatus, ChatMode
   - Language, ConfidenceLevel

6. **✅ Routes Organization**
   - Separate route files for each user role
   - Clean route structure

7. **✅ Sample Components**
   - Beautiful welcome page
   - Interactive ChatBox component
   - Main App layout

## 🎯 Getting Started

### 1. Start Development Servers

**Terminal 1 - Laravel:**
```bash
cd /Applications/MAMP/htdocs/uni-chat
php artisan serve
```

**Terminal 2 - Vite (for development with HMR):**
```bash
cd /Applications/MAMP/htdocs/uni-chat
npm run dev
```

### 2. Access the Application

- **Development:** http://localhost:8000
- **Vite HMR:** http://localhost:5173

### 3. View the Welcome Page

Open http://localhost:8000 in your browser to see:
- Beautiful gradient design
- Vue 3 + Vite + Tailwind status indicators
- Feature showcase
- Premium UI/UX

## 📁 Project Structure Overview

```
uni-chat/
├── app/
│   ├── Domain/          # Business logic
│   │   ├── User/
│   │   ├── Academic/
│   │   ├── Document/
│   │   ├── RAG/
│   │   ├── Chat/
│   │   └── Analytics/
│   ├── Infrastructure/  # External services
│   │   ├── AI/
│   │   ├── VectorDB/
│   │   ├── FileStorage/
│   │   └── Speech/
│   ├── Services/        # Application services
│   ├── Http/           # Controllers, Middleware
│   ├── Livewire/       # Livewire components
│   └── Enums/          # Type-safe enums
│
├── resources/
│   ├── js/
│   │   ├── App.vue          # Main Vue component
│   │   ├── app.js           # Entry point
│   │   └── components/      # Vue components
│   ├── views/               # Blade templates
│   └── css/                 # Styles
│
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── student.php     # Student routes
│   ├── faculty.php     # Faculty routes
│   └── admin.php       # Admin routes
│
└── config/
    ├── ai.php          # AI configuration
    ├── rag.php         # RAG settings
    ├── vector.php      # Vector DB
    └── permissions.php # Permissions
```

## 🔑 Key Files

| File | Purpose |
|------|---------|
| `resources/js/App.vue` | Main Vue component |
| `resources/js/components/ChatBox.vue` | Chat interface |
| `resources/views/welcome.blade.php` | Landing page |
| `resources/views/layouts/app.blade.php` | Main layout |
| `vite.config.js` | Vite + Vue configuration |
| `tailwind.config.js` | Tailwind CSS settings |

## 🎨 Design System

### Colors
- **Primary:** Indigo (600-900)
- **Secondary:** Purple (600-900)
- **Accent:** Pink (600-900)
- **Success:** Green (600-900)
- **Background:** Gradient from slate to indigo

### Components
- **Cards:** Rounded-2xl with hover effects
- **Buttons:** Gradient backgrounds
- **Inputs:** Focus rings and transitions
- **Status:** Pulse animations

### Dark Mode
Fully configured with `dark:` variants throughout.

## 🛠️ Available Commands

```bash
# Development
npm run dev          # Start Vite dev server with HMR
php artisan serve    # Start Laravel server

# Build
npm run build        # Build for production

# Database
php artisan migrate  # Run migrations (when created)
php artisan db:seed  # Seed database (when created)

# Cache
php artisan optimize:clear  # Clear all caches
php artisan view:clear      # Clear view cache

# Code Quality
php artisan test     # Run tests (when created)
```

## 🔄 What Works Now

✅ **Frontend**
- Vue 3 reactive components
- Vite hot module replacement
- Tailwind CSS styling
- Dark mode
- Responsive design

✅ **Backend**
- Laravel 11 routing
- Blade templates
- DDD structure
- Configuration system

✅ **Integration**
- Vue + Laravel seamless integration
- Asset compilation with Vite
- Component auto-loading

## 📝 Next Steps (Phase 2)

### Database & Models
1. Create migrations for:
   - Users table (with roles)
   - Documents table
   - Chats table
   - Messages table
   - Analytics table

2. Build Eloquent models in Domain layer
3. Set up relationships
4. Create seeders

### To Start Phase 2:
```bash
# Create your first migration
php artisan make:migration create_users_table

# Or use the Domain structure
php artisan make:model Domain/User/Models/User -m
```

## 🐛 Troubleshooting

### Asset not found errors
```bash
npm run build
php artisan view:clear
```

### Vite not connecting
Make sure both servers are running:
- Laravel: http://localhost:8000
- Vite: http://localhost:5173

### Node module errors
```bash
rm -rf node_modules package-lock.json
npm install
```

## 📚 Resources

- **Project Docs:** `README.md`
- **Structure:** `PROJECT_STRUCTURE.md`
- **Laravel 11:** https://laravel.com/docs/11.x
- **Vue 3:** https://vuejs.org/
- **Vite:** https://vitejs.dev/
- **Tailwind:** https://tailwindcss.com/

## 🎉 Success Indicators

You'll know everything is working when:
1. ✅ webpack builds without errors (`npm run build`)
2. ✅ Welcome page shows Vue 3 + Vite + Tailwind badges
3. ✅ Hot module replacement works in dev mode
4. ✅ Tailwind classes are applied correctly
5. ✅ Dark mode toggle works (when implemented)

---

**🎊 Phase 1 Complete! Ready for Phase 2: Database & Models**

Built with ❤️ using Laravel 11, Vue 3, Vite, and Tailwind CSS
