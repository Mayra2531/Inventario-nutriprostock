# NutriProStock - Sistema de Inventario

Sistema de gestión de inventario desarrollado en PHP y MySQL.

## Características

- Gestión de productos
- Control de entradas y salidas
- Categorías de productos
- Reportes mensuales
- Generación de PDFs
- Sistema de autenticación

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)

## Instalación

1. Clona este repositorio
2. Crea una base de datos MySQL llamada `inventario_db`
3. Copia `conexion.example.php` a `conexion.php`
4. Configura tus credenciales de base de datos en `conexion.php`
5. Importa el esquema de base de datos (si aplica)
6. Asegúrate de que la carpeta `uploads/` tenga permisos de escritura

## Uso

Accede al sistema mediante `login.php` con tus credenciales.

## Estructura del Proyecto

- `login.php` - Página de inicio de sesión
- `index.php` - Dashboard principal
- `productos.php` - Gestión de productos
- `categorias.php` - Gestión de categorías
- `entrada.php` - Registro de entradas
- `salida.php` - Registro de salidas
- `reportes/` - Módulo de generación de reportes
- `uploads/` - Archivos subidos por usuarios

## Seguridad

⚠️ **Importante**: Nunca subas el archivo `conexion.php` con credenciales reales a un repositorio público.

## Licencia

Uso interno - Todos los derechos reservados
