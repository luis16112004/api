# 📤 Comandos para Subir Cambios a Git

Ejecuta estos comandos en la terminal desde la carpeta del proyecto:

## 🚀 Comandos Rápidos

```bash
# 1. Ir a la carpeta del proyecto
cd android-app

# 2. Ver qué archivos han cambiado
git status

# 3. Agregar todos los cambios
git add .

# 4. Hacer commit con un mensaje descriptivo
git commit -m "Corregidos errores: referencias a MainActivity y dependencias faltantes"

# 5. Subir los cambios a GitHub
git push origin master
```

## 📋 Si es la primera vez o hay problemas

### Si no has configurado Git:

```bash
# Configurar tu nombre y email (solo la primera vez)
git config --global user.name "Tu Nombre"
git config --global user.email "tu@email.com"
```

### Si el branch se llama "main" en lugar de "master":

```bash
git push origin main
```

### Si necesitas hacer pull primero:

```bash
# Obtener cambios del repositorio remoto
git pull origin master

# Resolver conflictos si los hay, luego:
git add .
git commit -m "Corregidos errores"
git push origin master
```

## ✅ Verificación

Después de hacer push, verifica en GitHub:
- Ve a: https://github.com/luis16112004/puntoventa3
- Deberías ver los cambios reflejados

