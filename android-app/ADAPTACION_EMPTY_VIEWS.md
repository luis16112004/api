# 🔄 Adaptación para Empty Views Activity

Este proyecto ha sido adaptado para funcionar con la plantilla **"Empty Views Activity"** de Android Studio.

## 📋 Cambios Realizados

1. **MainActivity** como actividad principal (en lugar de LoginActivity)
2. Estructura compatible con la plantilla Empty Views Activity
3. ViewBinding configurado correctamente
4. Navegación desde MainActivity a las demás pantallas

## 🚀 Pasos para Configurar

### 1. Crear el Proyecto en Android Studio

1. Abre **Android Studio**
2. **File → New → New Project**
3. Selecciona **"Empty Views Activity"**
4. Configura:
   - **Name**: PuntoVentaApp
   - **Package name**: com.puntoventa.app
   - **Language**: Kotlin
   - **Minimum SDK**: API 24 (Android 7.0)

### 2. Copiar los Archivos

Copia todos los archivos de la carpeta `android-app` a tu proyecto nuevo, reemplazando los archivos existentes.

### 3. Configurar MainActivity

La `MainActivity` será tu pantalla de login. El código ya está adaptado.

### 4. Configurar la URL de la API

Edita `app/src/main/java/com/puntoventa/app/api/NetworkConfig.kt` y cambia la URL.

## ✅ Estructura Final

```
MainActivity (Login) → RegisterActivity → ProvidersActivity → AddProviderActivity
```

