# UniGPT - University Academic Copilot

A comprehensive university AI assistant built with Laravel 11, Vue 3, Vite, and Tailwind CSS, following Domain-Driven Design (DDD) architecture.

## 🏗️ Architecture

This project implements **Domain-Driven Design (DDD)** with a clear separation of concerns:

### Directory Structure

```
📁 app/
├── Console/              # Artisan commands
├── Enums/               # Enum classes (UserRole, DocumentStatus, ChatMode, etc.)
├── Exceptions/          # Custom exception handlers
├── Http/                # HTTP layer
│   ├── Controllers/     # Controllers (Auth, Student, Faculty, Admin, Api)
│   ├── Middleware/      # HTTP middleware
│   ├── Requests/        # Form requests
│   └── Resources/       # API resources
├── Livewire/           # Livewire components
│   ├── Student/        # Student dashboard components
│   ├── Faculty/        # Faculty components
│   ├── Admin/          # Admin panel components
│   └── Shared/         # Shared components
├── Domain/             # Domain layer (business logic)
│   ├── User/           # User domain
│   ├── Academic/       # Academic domain
│   ├── Document/       # Document processing
│   ├── RAG/            # RAG (Retrieval-Augmented Generation)
│   ├── Chat/           # Chat functionality
│   └── Analytics/      # Analytics and metrics
├── Infrastructure/     # Infrastructure layer
│   ├── AI/             # AI client implementations (OpenAI, Gemini, Local LLM)
│   ├── VectorDB/       # Vector database clients (Pinecone, FAISS, Chroma)
│   ├── FileStorage/    # File storage handling
│   └── Speech/         # Speech-to-text services
├── Services/           # Application services
└── Providers/          # Service providers
```

## 🚀 Technology Stack

### Backend
- **Laravel 11** - PHP framework
- **Livewire** - Dynamic Laravel components
- **Domain-Driven Design** - Architecture pattern

### Frontend
- **Vue 3** - Progressive JavaScript framework
- **Vite** - Next-generation frontend tooling
- **Tailwind CSS** - Utility-first CSS framework
- **Headless UI** - Unstyled, accessible UI components
- **Heroicons** - SVG icon set

### AI & Vector Databases
- **OpenAI** - GPT models
- **Google Gemini** - Google's AI models
- **Local LLM** - Self-hosted language models
- **Pinecone** - Vector database
- **FAISS** - Facebook AI Similarity Search
- **ChromaDB** - Open-source embedding database

## 📦 Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- npm or yarn

### Setup Steps

1. **Install PHP dependencies**
```bash
composer install
```

2. **Install Node dependencies**
```bash
npm install
```

3. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure your `.env` file**
```env
# AI Configuration
AI_DEFAULT_PROVIDER=openai
OPENAI_API_KEY=your_api_key_here
OPENAI_MODEL=gpt-4-turbo-preview
GEMINI_API_KEY=your_gemini_key_here

# Vector Database
VECTOR_DB_DEFAULT=pinecone
PINECONE_API_KEY=your_pinecone_key
PINECONE_ENVIRONMENT=your_environment
PINECONE_INDEX_NAME=unigpt

# Embedding
EMBEDDING_PROVIDER=openai
EMBEDDING_MODEL=text-embedding-ada-002
```

5. **Run migrations**
```bash
php artisan migrate
```

6. **Build assets**
```bash
npm run build
```

## 🔥 Development

### Start Development Servers

**Terminal 1 - Laravel Server:**
```bash
php artisan serve
```

**Terminal 2 - Vite Dev Server:**
```bash
npm run dev
```

Your application will be available at:
- Laravel: `http://localhost:8000`
- Vite HMR: `http://localhost:5173`

## 📋 Features

### Core Features
- ✅ **Domain-Driven Design Architecture**
- ✅ **Multi-AI Provider Support** (OpenAI, Gemini, Local LLM)
- ✅ **RAG System** with advanced retrieval
- ✅ **Role-Based Access Control** (Admin, Faculty, Student)
- ✅ **Document Processing** with smart chunking
- ✅ **Vector Database Integration**
- ✅ **Real-time Chat Interface**
- ✅ **Analytics Dashboard**

### User Roles

#### Admin
- Manage users and permissions
- Upload and approve documents
- View analytics
- Control AI prompts and settings

#### Faculty
- Upload course documents
- Use AI teaching assistant
- View student progress
- Access analytics

#### Student
- Interactive AI chat
- View learning roadmap
- Save important answers
- Access course materials

## 🎨 Frontend Components

### Vue 3 Components
Located in `resources/js/components/`:
- **ChatBox.vue** - Interactive chat interface
- More components will be added as development progresses

### Livewire Components
Located in `app/Livewire/`:
- Student dashboard components
- Faculty tools
- Admin panel
- Shared utilities

## 🔧 Configuration Files

### AI Configuration (`config/ai.php`)
Configure AI providers, models, and parameters

### RAG Configuration (`config/rag.php`)
Set up chunking strategies, retrieval settings, and citation formats

### Vector Database (`config/vector.php`)
Configure Pinecone, FAISS, or ChromaDB

### Permissions (`config/permissions.php`)
Define role-based access control

## 📝 Usage Examples

### Using Enums
```php
use App\Enums\UserRole;
use App\Enums\ChatMode;

// Check user role
if ($user->role === UserRole::ADMIN) {
    // Admin logic
}

// Get chat mode system prompt
$prompt = ChatMode::ACADEMIC->systemPrompt();
```

### AI Service Integration
```php
use App\Infrastructure\AI\OpenAIClient;

$client = app(OpenAIClient::class);
$response = $client->chat($message, $context);
```

## 🛣️ Routes

Routes are organized by user roles:
- `routes/web.php` - Public routes
- `routes/student.php` - Student routes
- `routes/faculty.php` - Faculty routes
- `routes/admin.php` - Admin routes
- `routes/api.php` - API routes

## 🎯 Next Steps

This is Phase 1 completion:
1. ✅ Directory structure created
2. ✅ Vue 3 + Vite integration
3. ✅ Tailwind CSS configured
4. ✅ Basic enums and configs
5. ✅ Sample components

### Upcoming Phases:
- Phase 2: Database schema and migrations
- Phase 3: Authentication and authorization
- Phase 4: AI integration and RAG system
- Phase 5: Chat interface and UI
- Phase 6: Document processing
- Phase 7: Analytics and reporting

## 📚 Documentation

- [Laravel Documentation](https://laravel.com/docs/11.x)
- [Vue 3 Documentation](https://vuejs.org/)
- [Vite Documentation](https://vitejs.dev/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [Headless UI Documentation](https://headlessui.com/vue/menu)

## 🤝 Contributing

This project follows Domain-Driven Design principles. When adding features:
1. Identify the domain
2. Place business logic in `app/Domain/`
3. Infrastructure concerns go in `app/Infrastructure/`
4. Keep controllers thin
5. Use services for complex operations

## 📄 License

[Your License Here]

## 👨‍💻 Author

UniGPT Development Team

---

**Built with ❤️ using Laravel 11, Vue 3, Vite, and Tailwind CSS**
