# 📱 Guía para Empty Views Activity

## 🎯 Pasos para Configurar el Proyecto

### 1. Crear el Proyecto en Android Studio

1. Abre **Android Studio**
2. **File → New → New Project**
3. Selecciona **"Empty Views Activity"**
4. Configura:
   - **Name**: PuntoVentaApp
   - **Package name**: com.puntoventa.app
   - **Language**: Kotlin
   - **Minimum SDK**: API 24 (Android 7.0)
5. Haz clic en **Finish**

### 2. Agregar Dependencias

Abre `app/build.gradle.kts` y agrega estas dependencias en la sección `dependencies`:

```kotlin
dependencies {
    // ... dependencias existentes ...
    
    // Retrofit para API calls
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")
    
    // Coroutines
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.6.2")
    
    // Material Design
    implementation("com.google.android.material:material:1.11.0")
    
    // RecyclerView
    implementation("androidx.recyclerview:recyclerview:1.3.2")
    
    // SwipeRefreshLayout
    implementation("androidx.swiperefreshlayout:swiperefreshlayout:1.1.0")
}
```

Y asegúrate de tener ViewBinding habilitado:

```kotlin
android {
    // ... otras configuraciones ...
    
    buildFeatures {
        viewBinding = true
    }
}
```

### 3. Configurar Permisos

En `AndroidManifest.xml`, agrega antes de `<application>`:

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
```

Y en la etiqueta `<application>`, agrega:

```xml
<application
    android:usesCleartextTraffic="true"
    ...>
```

### 4. Reemplazar MainActivity

1. Reemplaza el contenido de `MainActivity.kt` con el archivo proporcionado
2. Reemplaza `activity_main.xml` con el layout proporcionado

### 5. Copiar los Archivos del Proyecto

Copia estos archivos y carpetas a tu proyecto:

```
📁 app/src/main/java/com/puntoventa/app/
   ├── api/
   │   ├── ApiService.kt
   │   ├── NetworkConfig.kt
   │   ├── RetrofitClient.kt
   │   └── models/
   │       ├── User.kt
   │       ├── Provider.kt
   │       └── AuthResponse.kt
   ├── ui/
   │   ├── register/
   │   │   └── RegisterActivity.kt
   │   └── providers/
   │       ├── ProvidersActivity.kt
   │       ├── ProvidersAdapter.kt
   │       └── AddProviderActivity.kt
   └── utils/
       └── TokenManager.kt

📁 app/src/main/res/layout/
   ├── activity_register.xml
   ├── activity_providers.xml
   ├── activity_add_provider.xml
   └── item_provider.xml
```

### 6. Configurar la URL de la API

Edita `app/src/main/java/com/puntoventa/app/api/NetworkConfig.kt`:

```kotlin
const val BASE_URL = "https://tu-proyecto.railway.app"
```

### 7. Sincronizar y Ejecutar

1. **File → Sync Project with Gradle Files**
2. Espera a que termine la sincronización
3. Ejecuta la app (▶️)

## ✅ Estructura Final

```
MainActivity (Login) 
    ↓
RegisterActivity (Registro)
    ↓
ProvidersActivity (Lista de proveedores)
    ↓
AddProviderActivity (Agregar proveedor)
```

## 🔍 Verificación

1. La app inicia en `MainActivity` (pantalla de login)
2. Puedes registrarte o iniciar sesión
3. Después del login, vas a la lista de proveedores
4. Puedes agregar nuevos proveedores
5. Los datos se guardan en Firebase Realtime Database

## 📝 Notas Importantes

- **MainActivity** es ahora la pantalla de login
- ViewBinding está configurado automáticamente
- Todas las Activities usan ViewBinding
- El proyecto está listo para usar con la plantilla Empty Views Activity

## 🐛 Solución de Problemas

### Error: "Unresolved reference: binding"
- Verifica que ViewBinding esté habilitado en `build.gradle.kts`
- Sincroniza Gradle nuevamente

### Error: "Cannot find symbol: R"
- Limpia el proyecto: **Build → Clean Project**
- Reconstruye: **Build → Rebuild Project**

### La app no inicia
- Verifica que `MainActivity` esté en el AndroidManifest como LAUNCHER
- Verifica que el layout `activity_main.xml` exista

¡Listo! Tu proyecto está adaptado para Empty Views Activity 🎉

