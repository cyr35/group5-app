# Sistema de Asistencia Estudiantil - Versión Mejorada

Este es un sistema completo y funcional de registro de asistencia de estudiantes creado con PHP y MariaDB/MySQL, con mejoras significativas en seguridad, funcionalidad y diseño.

## 🚀 Características Principales

- ✅ **Sistema de autenticación seguro** con contraseñas hasheadas
- 👨‍🏫 **Panel para profesores** con registro y gestión de asistencia
- 👨‍🎓 **Panel para estudiantes** con visualización de su historial
- 📊 **Dashboard con estadísticas** y métricas de asistencia
- 📱 **Diseño responsive** con Bootstrap 5
- 🔒 **Seguridad mejorada** con consultas preparadas
- 🎨 **Interfaz moderna** con gradientes y animaciones
- 📅 **Selector de fechas** para registro histórico
- 📈 **Reportes visuales** de asistencia

## 📂 Estructura del Proyecto

```
attendance_system/
├── css/
│   └── style.css                 # Estilos CSS mejorados
├── php/
│   ├── config.php               # Configuración y funciones de seguridad
│   ├── db_connect.php           # Clase de conexión a BD mejorada
│   ├── login.php                # Sistema de login con validación
│   ├── dashboard.php            # Panel principal diferenciado por rol
│   ├── register_attendance.php  # Registro de asistencia mejorado
│   └── logout.php               # Cierre de sesión seguro
├── sql/
│   └── attendance_system.sql    # Base de datos con datos de ejemplo
└── README.md                    # Este archivo
```

## 🛠️ Instalación y Configuración

### 1. Requisitos Previos
- PHP 7.4 o superior
- MySQL/MariaDB
- Servidor web (Apache/Nginx)
- Extensiones PHP: mysqli, session

### 2. Configurar la Base de Datos

1. **Crear la base de datos:**
   ```sql
   CREATE DATABASE attendance_system;
   ```

2. **Importar el schema:**
   - Ejecuta el archivo `sql/attendance_system.sql` en tu base de datos
   - Esto creará las tablas `users` y `attendance` con datos de ejemplo

### 3. Configurar la Conexión

1. **Editar `php/config.php`:**
   ```php
   define('DB_HOST', 'localhost');        // Tu host
   define('DB_USERNAME', 'tu_usuario');   // Tu usuario de BD
   define('DB_PASSWORD', 'tu_contraseña'); // Tu contraseña de BD
   define('DB_NAME', 'attendance_system');
   ```

### 4. Desplegar la Aplicación

1. **Copiar archivos:**
   - Coloca la carpeta `attendance_system` en tu directorio web (`htdocs`, `www`, etc.)

2. **Configurar permisos:**
   ```bash
   chmod 755 attendance_system/
   chmod 644 attendance_system/php/*
   ```

3. **Acceder a la aplicación:**
   - Navega a: `http://localhost/attendance_system/php/login.php`

## 👥 Usuarios de Demostración

El sistema incluye usuarios predefinidos para pruebas:

### Profesor
- **Usuario:** `profesor1`
- **Contraseña:** `teacher_password`
- **Permisos:** Registrar asistencia, ver reportes

### Estudiantes
- **Usuario:** `estudiante1` / **Contraseña:** `student_password`
- **Usuario:** `estudiante2` / **Contraseña:** `student_password`
- **Permisos:** Ver su historial de asistencia

## 🔧 Nuevas Funcionalidades Implementadas

### Seguridad Mejorada
- ✅ Contraseñas hasheadas con `password_hash()`
- ✅ Consultas preparadas (prepared statements)
- ✅ Validación de entrada de datos
- ✅ Protección contra XSS
- ✅ Control de sesiones seguro

### Interfaz Mejorada
- ✅ Diseño moderno con Bootstrap 5
- ✅ Iconos Font Awesome
- ✅ Gradientes y animaciones CSS
- ✅ Modo responsive para móviles
- ✅ Feedback visual mejorado

### Funcionalidades Adicionales
- ✅ Selector de fechas para registro histórico
- ✅ Botones de acción rápida (marcar todos)
- ✅ Estadísticas detalladas por rol
- ✅ Notas opcionales en registros
- ✅ Validación de fechas duplicadas

## 📊 Capturas de Pantalla

### Login
- Interfaz moderna con credenciales de demo visibles
- Validación de formularios en tiempo real

### Dashboard Profesor
- Estadísticas de estudiantes totales
- Registro de asistencia del día
- Historial de asistencias recientes

### Dashboard Estudiante
- Estadísticas personales de asistencia
- Porcentaje de asistencia calculado
- Historial completo con fechas y notas

### Registro de Asistencia
- Lista completa de estudiantes
- Estados: Presente, Tarde, Ausente
- Campo de notas opcional
- Botones de acción masiva

## 🔍 Problemas Solucionados

### Del Sistema Original:
1. **Contraseñas en texto plano** → Ahora hasheadas con bcrypt
2. **SQL Injection vulnerable** → Consultas preparadas implementadas
3. **Diseño básico** → Interfaz moderna con Bootstrap 5
4. **Sin validación de datos** → Validación completa implementada
5. **Sesiones inseguras** → Control de sesiones mejorado
6. **Sin manejo de errores** → Manejo completo de excepciones

### Mejoras Adicionales:
- Base de datos normalizada
- Arquitectura orientada a objetos para BD
- Separación de responsabilidades
- Código documentado y limpio
- Responsive design
- Accesibilidad mejorada

## 🚀 Cómo Usar el Sistema

### Para Profesores:
1. **Iniciar sesión** con credenciales de profesor
2. **Ver dashboard** con estadísticas generales
3. **Registrar asistencia:**
   - Ir a "Registrar Asistencia"
   - Seleccionar fecha (por defecto hoy)
   - Marcar estado de cada estudiante
   - Agregar notas opcionales
   - Guardar cambios

### Para Estudiantes:
1. **Iniciar sesión** con credenciales de estudiante
2. **Ver dashboard** con estadísticas personales
3. **Revisar historial** de asistencia completo
4. **Ver porcentaje** de asistencia calculado

## 🔧 Personalización

### Cambiar Colores
Edita las variables CSS en `css/style.css`:
```css
:root {
    --primary-gradient: linear-gradient(135deg, #tu-color-1 0%, #tu-color-2 100%);
    --success-gradient: linear-gradient(135deg, #tu-verde-1 0%, #tu-verde-2 100%);
}
```

### Agregar Nuevos Usuarios
```sql
-- Para profesores
INSERT INTO users (username, password, role, full_name, email) VALUES 
('nuevo_profesor', '$2y$10$hash_de_contraseña', 'teacher', 'Nombre Completo', 'email@example.com');

-- Para estudiantes  
INSERT INTO users (username, password, role, full_name, email) VALUES 
('nuevo_estudiante', '$2y$10$hash_de_contraseña', 'student', 'Nombre Completo', 'email@example.com');
```

### Generar Hash de Contraseña
```php
<?php
echo password_hash('tu_contraseña', PASSWORD_DEFAULT);
?>
```

## 🐛 Solución de Problemas

### Error de Conexión a BD
- Verifica credenciales en `config.php`
- Asegúrate que MySQL/MariaDB esté ejecutándose
- Confirma que la base de datos existe

### Problemas de Sesión
- Verifica que PHP tenga permisos para escribir sesiones
- Comprueba configuración de `session.save_path`

### Errores de Permisos
- Asegúrate que los archivos tengan permisos de lectura
- El directorio web debe ser accesible por el servidor

## 🤝 Contribuir

Para contribuir al proyecto:
1. Fork del repositorio
2. Crear rama para nueva funcionalidad
3. Implementar cambios con tests
4. Enviar pull request con descripción detallada

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Consulta el archivo LICENSE para más detalles.

## 📞 Soporte

Para soporte técnico o reportar bugs:
- Crear un issue en el repositorio
- Incluir detalles del error y pasos para reproducir
- Especificar versión de PHP y base de datos utilizada

---

**Desarrollado con ❤️ para la gestión educativa moderna**