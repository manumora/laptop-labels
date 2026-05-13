# laptop-labels

Web application to generate **PDF labels** for student laptops in Extremadura (Spain) high schools. It connects to the school's LDAP directory to retrieve student data and produces printable label sheets with QR codes and optional student photos.

## Features

- Authenticates against the school's LDAP server using staff credentials.
- Retrieves classroom groups and their members from LDAP.
- Generates a **PDF label sheet** for one or more selected classroom groups, each label containing:
  - Student full name and username.
  - A **QR code** encoding the student's vCard (name, ID number, organisation, address, phone, email, web).
  - Student **photo** (read from the `jpegPhoto` LDAP attribute when available).
- Configurable label layout: rows, columns, margins, label width/height, QR code size, photo dimensions.
- **Offset support**: start printing from a specific label position on the sheet (useful when reusing a partially-used label sheet).
- Optional **UID filter** to restrict output to specific students.
- Also generates an **ODS spreadsheet** (OpenDocument) listing students grouped by classroom.

## Requirements

- PHP 5+ with the following extensions enabled:
  - `ldap`
  - `gd` (for photo processing)
- An LDAP server following the Linex/Extremadura school directory schema (`ou=People`, `ou=Group`, `dc=instituto,dc=extremadura,dc=es`).
- A web server (Apache, Nginx, etc.) with PHP support.

## Dependencies (bundled)

| Library | Version | Purpose |
|---|---|---|
| [FPDF](http://www.fpdf.org/) | — | PDF generation |
| [QR code generator](qr/qr_img.php) | — | QR code image creation |
| [php-ods](ods/) | — | ODS spreadsheet generation |
| [jQuery](jquery/) | 1.4.2 | Frontend interactions |
| [jQuery UI](jquery/ui/) | 1.8.5 | UI widgets |

## Installation

1. Copy the project folder to your web server's document root.
2. Ensure the `tmp/` directory exists and is writable by the web server (it is used as a scratch space for QR and photo images during PDF generation).
3. Open `index.php` in a browser.

## Usage

1. Enter your LDAP host and credentials.
2. Select one or more classroom groups.
3. Configure the label sheet dimensions and optional fields (organisation name, address, phone, email, web URL).
4. Click **Generate PDF** to download the label sheet, or **Generate Spreadsheet** to download an ODS file.

## License

GNU General Public License v3.0 — see [LICENSE](LICENSE).

## Author

Manu Mora Gordillo &lt;manuel.mora.gordillo@gmail.com&gt; — 2010
