# AulaCode – Plataforma de Aprendizaje, Práctica y Evaluación

Aplicación web educativa para aulas informáticas en red LAN (XAMPP + PHP + MySQL).

**Versión actual:** Fase 1 (estructura, base de datos, login, roles y dashboards básicos).

---

## Requisitos

- Windows
- [XAMPP](https://www.apachefriends.org/) con Apache, MySQL/MariaDB y PHP 8+
- Navegador web en el PC del profesor y en los PCs de los alumnos

Los ordenadores de los alumnos **no** necesitan XAMPP.

---

## Instalación (PC del profesor)

### 1. Instalar e iniciar XAMPP

1. Instala XAMPP.
2. Abre el **Panel de control de XAMPP**.
3. Pulsa **Start** en **Apache**.
4. Pulsa **Start** en **MySQL**.

### 2. Copiar el proyecto

Copia la carpeta `aulacode` dentro de:

```text
C:\xampp\htdocs\aulacode
```

### 3. Crear / importar la base de datos

1. Abre phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Ve a la pestaña **Importar**.
3. Selecciona el archivo:

```text
C:\xampp\htdocs\aulacode\database.sql
```

4. Pulsa **Continuar** / **Importar**.

También puedes ejecutar el contenido de `database.sql` en la pestaña **SQL**.

### 4. Configurar la conexión (si es necesario)

Edita:

```text
aulacode/config/database.php
```

Valores por defecto de XAMPP:

| Constante | Valor por defecto |
|-----------|-------------------|
| `DB_HOST` | `127.0.0.1` |
| `DB_NAME` | `aulacode` |
| `DB_USER` | `root` |
| `DB_PASS` | *(vacío)* |

Si tu MySQL tiene contraseña, indícala en `DB_PASS`.

### 5. Abrir AulaCode

En el PC del profesor:

```text
http://localhost/aulacode/
```

---

## Usuarios de prueba (Fase 1)

| Rol | Usuario | Contraseña |
|-----|---------|------------|
| Profesor / Admin | `admin` | `admin123` |
| Alumno | `alumno1` | `alumno123` |

Las contraseñas están almacenadas con `password_hash()` (nunca en texto plano).

---

## Acceso desde los PCs de los alumnos (LAN)

1. Conecta todos los equipos a la **misma red** (Wi‑Fi o cable).
2. En el PC del profesor, abre CMD o PowerShell y ejecuta:

```text
ipconfig
```

3. Anota la **dirección IPv4** (ejemplo: `192.168.1.100`).
4. En Windows Firewall, permite **Apache HTTP Server** en redes **privadas**.
5. Desde un alumno, abre el navegador:

```text
http://IP_DEL_PROFESOR/aulacode/
```

Ejemplo:

```text
http://192.168.1.100/aulacode/
```

---

## Estructura del proyecto (Fase 1)

```text
aulacode/
├── config/           # Configuración y bootstrap
├── includes/         # Auth, helpers, layout
├── auth/             # Login / logout
├── admin/            # Panel del profesor
├── alumno/           # Panel del alumno
├── assets/css|js|images
├── database.sql
├── index.php
└── README.md
```

Carpetas reservadas para fases posteriores: `ejercicios/`, `preguntas/`, `resultados/`, `materiales/`, `sql/`, `uploads/`, `database/`.

---

## Qué incluye la Fase 1

- Estructura organizada del proyecto
- Base de datos `aulacode` (tablas y relaciones)
- Conexión PDO con consultas preparadas
- Login con detección automática de rol
- Sesiones PHP + CSRF en el formulario de login
- Dashboards básicos de profesor y alumno
- CSS/JS locales (funciona sin Internet)

## Qué NO incluye todavía (próximas fases)

- Gestión de alumnos / ejercicios / preguntas
- Temporizador y realización de ejercicios
- Corrección automática y estadísticas
- Entorno SQL seguro
- Materiales, exportaciones y backup guiado

---

## Copia de seguridad (resumen)

En phpMyAdmin:

1. Selecciona la base `aulacode`.
2. Pestaña **Exportar**.
3. Método **Rápido**, formato **SQL**.
4. Guarda el archivo en un pendrive o carpeta segura.

(La sección ampliada de backup se completará en la Fase 7.)

---

## Desarrollo por fases

| Fase | Contenido |
|------|-----------|
| **1** | Estructura, BD, login, roles, dashboards |
| 2 | Alumnos, grupos, ejercicios, preguntas |
| 3 | Realizar ejercicios, temporizador, finalizar |
| 4 | Corrección automática, resultados, estadísticas |
| 5 | Ejercicios SQL seguros |
| 6 | Materiales, progreso, dashboard avanzado |
| 7 | Exportaciones, backup, optimización, docs |

---

## Solución de problemas

| Problema | Comprobación |
|----------|--------------|
| Página en blanco / error de conexión | Apache y MySQL iniciados; `database.php` correcto; BD importada |
| No cargan CSS | Accede por `http://localhost/aulacode/` (no abras el PHP como archivo) |
| Los alumnos no conectan | Misma LAN, IP correcta, firewall de Apache permitido |
| Login incorrecto | Usa `admin` / `admin123` o `alumno1` / `alumno123` tras importar `database.sql` |
