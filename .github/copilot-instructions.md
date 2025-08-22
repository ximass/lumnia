The system is a chat with LLM integration. The system contains various knowledge bases, which users create and feed with files (documents).

All questions must be answered based on the project technologies:
- The /api repository is in Laravel version 11, using the PostgreSQL database, and native Laraval broadcast using Pusher.
- The /front repository is in Vue 3, using Typescript and the Vuetify component library. All screens must use Vuetify components and classes, always focusing on the most appropriate component for the request. The logic present on the components must use the types. The Types must be saved on the file src/types/types.ts.
- Dont use Title case on the labels, always use uppercase first letter and lowercase the rest, except for acronyms.
- Dont comment the code, except when necessary to explain complex logic. The comments must be in English.
