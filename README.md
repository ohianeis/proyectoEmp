<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# 📌 Proyecto Empresa – Aplicación de Gestión (API Headless Edition)

Aplicación web desarrollada como proyecto formativo en el ciclo **DAW/DAM**, orientada a la gestión de incidencias, usuarios y tareas en un entorno empresarial simulado.  
El objetivo es aplicar buenas prácticas de desarrollo backend con **Laravel** y documentar la API con **Swagger**.

---

## 🛑 Sobre esta rama (`feature/hardened-api-prod`)
Esta rama contiene la versión optimizada para **producción pura**. Se ha transformado el proyecto en una arquitectura **Headless**:
- **Sin Frontend:** Se ha eliminado Vite, npm y todas las vistas Blade (`welcome.blade.php`).
- **Respuesta JSON:** La raíz del servidor (`/`) devuelve el estado de la API en formato JSON.
- **Seguridad:** La documentación Swagger está protegida por Middleware y se oculta automáticamente (Error 404) si el servidor está en entorno de producción.

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
   git clone [https://github.com/ohianeis/proyectoEmp.git](https://github.com/ohianeis/proyectoEmp.git)
   cd proyectoEmp