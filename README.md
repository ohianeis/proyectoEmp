<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# 📌 Proyecto Empresa – Aplicación de Gestión

Aplicación web desarrollada como proyecto formativo en el ciclo **DAW/DAM**, orientada a la gestión de incidencias, usuarios y tareas en un entorno empresarial simulado.  
El objetivo es aplicar buenas prácticas de desarrollo backend con **Laravel** y documentar la API con **Swagger**.

---

## 🚀 Tecnologías utilizadas
- **Backend:** Laravel (PHP)
- **Base de datos:** MySQL
- **Documentación API:** Swagger
- **Control de versiones:** Git y GitHub

---

## ⚙️ Instalación y configuración

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/ohianeis/proyectoEmp.git
   cd proyectoEmp
2. **Instalar dependencias**
 
   composer install
   
   npm install
   
5. **Configurar variables de entorno**
   
   cp .env.example .env
   
   editar .env con los datos de tu base de datos local
   
   `ejemplo`
   
   DB_CONNECTION=mysql
   
   DB_HOST=127.0.0.1
   
   DB_PORT=3306
   
   DB_DATABASE=proyecto_emp
   
   DB_USERNAME=`root`
   
   DB_PASSWORD=
   
7. **Generar clave de la aplicación**
   
   php artisan key:generate
   
9. **Ejecutar migraciones y seeders**
    
   php artisan migrate --seed
   
11. **Levantar servidor**
    
   php artisan serve
   
📖 **Documentación de la API**

Este proyecto incluye documentación interactiva con Swagger.

<img width="492" height="288" alt="screenEnlaceSwagger" src="https://github.com/user-attachments/assets/dfab6010-4b09-46bd-8aa1-5ad7fbb66f9a" />

<img width="2560" height="1239" alt="screenApiSwagger" src="https://github.com/user-attachments/assets/38952d68-fb5e-45c0-aeb9-1866407c3496" />


✨ **Funcionalidades principales**

La aplicación implementa un sistema de gestión con diferentes roles de usuario:

### 🔑 Autenticación e inicio

- Registro e inicio de sesión con roles diferenciados: **Administrador**, **Empresa** y **Demandante**.
- 
- Control de acceso según permisos.

### 👤 Rol Administrador

- Validación y gestión de usuarios registrados.
  
- Administración de títulos del centro (para relacionar con ofertas y demandantes).
  
- Visualización de informes globales.
  

### 🏢 Rol Empresa

- Edición de perfil de empresa.
  
- Creación, edición y cierre de ofertas de empleo.
  
- Visualización de demandantes que poseen los títulos requeridos por sus ofertas (aunque no estén inscritos).
  
- Asignación de demandantes a ofertas.

### 🙋 Rol Demandante

- Edición de perfil personal.
  
- Asignación de títulos disponibles en el centro para relacionarlos con ofertas.
  
- Visualización de ofertas según sus títulos.
  
- Inscripción en ofertas disponibles.







