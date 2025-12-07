# 📱 Punto de Venta - Android App

Aplicación Android para gestionar proveedores conectada a Firebase Realtime Database.

## 🚀 Configuración Inicial

### 1. Clonar el Repositorio

```bash
git clone https://github.com/luis16112004/puntoventa3.git
cd puntoventa3
```

### 2. Abrir en Android Studio

1. Abre **Android Studio**
2. **File → Open** → Selecciona la carpeta del proyecto
3. Espera a que Gradle sincronice (puede tardar unos minutos)

### 3. ⚠️ IMPORTANTE: Configurar la URL de la API

Edita el archivo:
```
app/src/main/java/com/puntoventa/app/api/NetworkConfig.kt
```

Cambia la URL:
```kotlin
const val BASE_URL = "https://tu-proyecto.railway.app"
```

Por la URL real de tu proyecto en Railway.

### 4. Ejecutar la Aplicación

1. Conecta un dispositivo Android o inicia un emulador
2. Haz clic en el botón **Run** (▶️) o presiona `Shift + F10`
3. Selecciona tu dispositivo/emulador

## 📱 Funcionalidades

- ✅ **Login** - Iniciar sesión con email y contraseña
- ✅ **Registro** - Crear nueva cuenta de usuario
- ✅ **Lista de Proveedores** - Ver todos los proveedores guardados
- ✅ **Agregar Proveedor** - Crear nuevos proveedores
- ✅ **Pull to Refresh** - Actualizar lista deslizando hacia abajo
- ✅ **Logout** - Cerrar sesión

## 🏗️ Estructura del Proyecto

```
app/src/main/java/com/puntoventa/app/
├── MainActivity.kt              # Pantalla principal (Login)
├── api/                         # Configuración de API
│   ├── NetworkConfig.kt         # ⚠️ Configura la URL aquí
│   ├── ApiService.kt
│   ├── RetrofitClient.kt
│   └── models/                  # Modelos de datos
├── ui/
│   ├── register/
│   │   └── RegisterActivity.kt
│   └── providers/
│       ├── ProvidersActivity.kt
│       ├── ProvidersAdapter.kt
│       └── AddProviderActivity.kt
└── utils/
    └── TokenManager.kt          # Gestión de tokens
```

## 📦 Dependencias

- **Retrofit 2.9.0** - Para llamadas HTTP
- **Gson** - Serialización JSON
- **Coroutines** - Operaciones asíncronas
- **Material Design** - Componentes UI
- **RecyclerView** - Listas
- **SwipeRefreshLayout** - Pull to refresh

## 🔧 Solución de Problemas

### Error: "Unresolved reference"
- **Solución**: Sincroniza Gradle: **File → Sync Project with Gradle Files**

### Error: "Cannot find symbol: R"
- **Solución**: 
  1. **Build → Clean Project**
  2. **Build → Rebuild Project**

### Error de conexión
- **Solución**: Verifica que:
  1. La URL en `NetworkConfig.kt` sea correcta
  2. Tu API esté desplegada y funcionando
  3. Tengas conexión a internet

### La app no compila
- **Solución**:
  1. Verifica que todas las dependencias estén descargadas
  2. **File → Invalidate Caches / Restart**
  3. Sincroniza Gradle nuevamente

## 📝 Notas

- El proyecto usa **Kotlin**
- **ViewBinding** está habilitado
- **Minimum SDK**: API 24 (Android 7.0)
- **Target SDK**: API 34

## 🔗 Enlaces

- **Repositorio**: https://github.com/luis16112004/puntoventa3
- **API Backend**: Configura en `NetworkConfig.kt`

## ✅ Verificación

Para verificar que todo funciona:

1. Ejecuta la app
2. Registra un usuario nuevo
3. Inicia sesión
4. Agrega un proveedor
5. Verifica en Firebase Console que los datos se guardaron

---

**Desarrollado para Punto de Venta** 🛒
