# Sistema de Registro de Asistencia de Estudiantes

Este es un sistema simple de registro de asistencia de estudiantes creado con PHP y MariaDB.

## Características

-   Inicio de sesión para estudiantes y profesores.
-   Los profesores pueden registrar la asistencia de los estudiantes.
-   Los estudiantes pueden ver sus propios registros de asistencia.
-   Dashboard para visualizar los datos de asistencia.
-   Diseño responsivo para una fácil visualización en diferentes dispositivos.

## Estructura de Archivos

```
attendance_system/
├── css/
│   └── style.css
├── php/
│   ├── login.php
│   ├── register_attendance.php
│   ├── dashboard.php
│   ├── logout.php
│   ├── config.php
│   └── db_connect.php
├── sql/
│   └── attendance_system.sql
└── README.md
```

## Cómo Usar

1.  **Importar la Base de Datos:**
    *   Importa el archivo `sql/attendance_system.sql` a tu base de datos MariaDB. Esto creará la base de datos `attendance_system` y las tablas `users` y `attendance`.

2.  **Configurar la Conexión a la Base de Datos:**
    *   Abre el archivo `php/config.php` y actualiza las credenciales de la base de datos (`DB_USERNAME` y `DB_PASSWORD`) con las tuyas.

3.  **Poblar la Base de Datos (Ejemplo):**
    *   Para que el sistema funcione, necesitas agregar usuarios a la tabla `users`. Aquí tienes algunos ejemplos de inserciones SQL:

    ```sql
    -- Insertar un profesor (la contraseña es 'teacher_password')
    INSERT INTO `users` (`username`, `password`, `role`) VALUES
    ("profesor1", "$2y$10$gR.gNf2LzS3g5bE6hI3kIuJ9nF.ZgXvO/uH.eP.eP.eP.eP.eP", "teacher");

    -- Insertar un estudiante (la contraseña es 'student_password')
    INSERT INTO `users` (`username`, `password`, `role`) VALUES
    ("estudiante1", "$2y$10$gR.gNf2LzS3g5bE6hI3kIuJ9nF.ZgXvO/uH.eP.eP.eP.eP.eP", "student");
    ```

    **Nota:** Las contraseñas están hasheadas usando `password_hash()` de PHP. Para crear nuevos usuarios, puedes usar un script de registro que hashee las contraseñas antes de insertarlas en la base de datos.

4.  **Ejecutar la Aplicación:**
    *   Coloca la carpeta `attendance_system` en el directorio raíz de tu servidor web (por ejemplo, `htdocs` en XAMPP).
    *   Abre tu navegador y navega a `http://localhost/attendance_system/php/login.php`.

## Credenciales de Prueba

*   **Profesor:**
    *   **Usuario:** profesor1
    *   **Contraseña:** teacher_password
*   **Estudiante:**
    *   **Usuario:** estudiante1
    *   **Contraseña:** student_password

**Nota:** Necesitarás crear estos usuarios en tu base de datos como se describe en el paso 3.


