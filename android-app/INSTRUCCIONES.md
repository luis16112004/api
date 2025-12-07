# 📱 Instrucciones para Usar el Proyecto Android

## 🚀 Pasos para Configurar el Proyecto

### 1. Abrir en Android Studio

1. Abre **Android Studio**
2. Selecciona **File → Open**
3. Navega a la carpeta `android-app` y selecciónala
4. Espera a que Android Studio sincronice el proyecto (Gradle Sync)

### 2. Configurar la URL de tu API

**IMPORTANTE:** Debes cambiar la URL de tu API antes de ejecutar la app.

1. Abre el archivo: `app/src/main/java/com/puntoventa/app/api/NetworkConfig.kt`
2. Cambia esta línea:
   ```kotlin
   const val BASE_URL = "https://tu-proyecto.railway.app"
   ```
3. Por la URL real de tu proyecto en Railway, por ejemplo:
   ```kotlin
   const val BASE_URL = "https://mi-api-production.up.railway.app"
   ```

### 3. Sincronizar Gradle

1. Si Android Studio no sincroniza automáticamente, haz clic en **File → Sync Project with Gradle Files**
2. Espera a que termine la descarga de dependencias

### 4. Ejecutar la Aplicación

1. Conecta un dispositivo Android o inicia un emulador
2. Haz clic en el botón **Run** (▶️) o presiona `Shift + F10`
3. Selecciona tu dispositivo/emulador
4. La app se instalará y ejecutará automáticamente

## 📋 Funcionalidades de la App

### Login
- Ingresa con email y contraseña de un usuario registrado
- Si no tienes cuenta, puedes registrarte

### Registro
- Crea una nueva cuenta
- Selecciona el rol: Vendedor o Admin
- Después del registro, inicia sesión automáticamente

### Lista de Proveedores
- Muestra todos los proveedores guardados en Firebase
- Arrastra hacia abajo para actualizar (Pull to Refresh)
- Botón flotante (+) para agregar nuevo proveedor

### Agregar Proveedor
- Formulario completo con todos los campos
- Validación de datos
- Guarda directamente en Firebase Realtime Database

## 🔧 Solución de Problemas

### Error: "Unable to resolve host"
- Verifica que la URL en `NetworkConfig.kt` sea correcta
- Asegúrate de tener conexión a internet
- Si usas emulador, verifica que la URL sea accesible

### Error: "401 Unauthorized"
- Verifica que el token se esté guardando correctamente
- Intenta hacer login nuevamente

### Error: "Connection refused"
- Verifica que tu API esté desplegada y funcionando en Railway
- Prueba la URL en un navegador o con Postman

### La app no compila
- Verifica que todas las dependencias estén descargadas
- Haz clic en **File → Invalidate Caches / Restart**
- Sincroniza Gradle nuevamente

## 📱 Estructura de la App

```
LoginActivity (Pantalla inicial)
    ↓
RegisterActivity (Si no tienes cuenta)
    ↓
ProvidersActivity (Lista de proveedores)
    ↓
AddProviderActivity (Agregar nuevo proveedor)
```

## 🎨 Personalización

Puedes personalizar:
- Colores en `res/values/colors.xml`
- Strings en `res/values/strings.xml`
- Layouts en `res/layout/`

## ✅ Verificar que Funciona

1. **Registra un usuario** desde la app
2. **Inicia sesión** con ese usuario
3. **Agrega un proveedor** desde la app
4. **Verifica en Firebase Console** que los datos se guardaron:
   - Ve a Firebase Console
   - Selecciona tu proyecto
   - Ve a Realtime Database
   - Deberías ver `/users` y `/providers` con los datos

## 📞 Soporte

Si tienes problemas:
1. Revisa los logs en Android Studio (Logcat)
2. Verifica que la API esté funcionando
3. Revisa la configuración de Firebase

