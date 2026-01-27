
# wdpai_2025

## Opis projektu
Platforma do przeglądania, oceniania i dodawania memów z systemem poziomów, nagród i questów. Projekt oparty o PHP, PostgreSQL, JavaScript oraz Docker.

## Zaimplementowane zabezpieczenia

### 1. Ochrona przed SQL Injection
- **Jak:** Wszystkie zapytania do bazy danych realizowane są przez przygotowane zapytania (prepared statements) z bindowaniem parametrów.
- **Gdzie:**
	- src/repository/UserRepository.php
	- src/repository/Repository.php
	- src/repository/PostsRepository.php, QuestsRepository.php, itd.

### 2. Singleton Database Connection
- **Jak:** Klasa Database korzysta z wzorca Singleton, aby zapewnić jedno połączenie z bazą danych w całej aplikacji.
- **Gdzie:**
	- Database.php
	- src/repository/Repository.php (użycie Database::getInstance())

### 3. Bezpieczne logowanie i rejestracja
- **Jak:**
	- Hasła są hashowane (password_hash, password_verify)
	- Błędy logowania są generyczne (nie ujawniają, czy email istnieje)
	- Opóźnienie przy błędnych danych (usleep) chroni przed atakami timingowymi
	- Walidacja i sanityzacja danych wejściowych (email, username, hasło)
	- Regeneracja ID sesji po zalogowaniu (session_regenerate_id)
- **Gdzie:**
	- src/controllers/SecurityController.php

### 4. Wymuszanie HTTPS
- **Jak:** Przekierowanie na HTTPS na stronach logowania i rejestracji (z wyjątkiem localhost).
- **Gdzie:**
	- src/controllers/SecurityController.php (metoda enforceHttps)

### 5. Nagłówki bezpieczeństwa HTTP
- **Jak:**
	- X-Content-Type-Options: nosniff
	- X-Frame-Options: DENY
	- X-XSS-Protection: 1; mode=block
	- Referrer-Policy: strict-origin-when-cross-origin
- **Gdzie:**
	- index.php

### 6. Bezpieczna konfiguracja sesji
- **Jak:**
	- session.cookie_httponly = 1
	- session.cookie_secure = 1 (jeśli HTTPS)
	- session.cookie_samesite = Strict
	- session.use_strict_mode = 1
- **Gdzie:**
	- index.php

### 7. Walidacja i sanityzacja danych wejściowych
- **Jak:**
	- Walidacja formatu email (filter_var)
	- Walidacja długości i formatu hasła oraz username
	- Sanityzacja stringów (htmlspecialchars, trim)
- **Gdzie:**
	- src/controllers/SecurityController.php

### 8. Ochrona uploadu plików
- **Jak:**
	- Sprawdzanie typu MEMA (tylko obrazy JPEG, PNG, WebP)
	- Limit rozmiaru pliku (max 5MB)
	- Unikalna nazwa pliku
- **Gdzie:**
	- src/controllers/SecurityController.php (handleProfilePhotoUpload)

### 9. Ochrona endpointów API
- **Jak:**
	- Sprawdzanie sesji użytkownika przed akcjami wymagającymi autoryzacji
	- Odpowiedzi JSON nie zawierają wrażliwych danych
- **Gdzie:**
	- src/controllers/DashboardController.php

## Struktura projektu

- config.php — konfiguracja bazy danych
- Database.php — singleton, połączenie z bazą
- docker-compose.yml, docker/ — konfiguracja środowiska Docker
- public/ — pliki statyczne (JS, CSS, obrazy)
- public/views/ — szablony HTML
- src/controllers/ — logika kontrolerów
- src/repository/ — dostęp do bazy danych

## Uruchomienie projektu

1. Zainstaluj Docker i Docker Compose
2. Sklonuj repozytorium i przejdź do katalogu projektu
3. Uruchom: `docker-compose up --build`
4. Aplikacja będzie dostępna pod adresem http://localhost:8080

## Autor
Karol
