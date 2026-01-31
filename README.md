# MemeSwipe

**💻Koncepcja Podstawowa:**
"Tinder dla Memów". MemeSwipe to platforma do przeglądania, oceniania i dodawania memów z systemem poziomów, nagród i questów, której fundamentem jest uzależniający system grywalizacji.

**🎯Cel:** 
Stworzenie "domyślnego" miejsca dla konsumpcji memów, które poprzez mechanikę gry (progresja, rywalizacja, nagrody) zatrzymuje użytkownika i motywuje go do codziennej aktywności. Aplikacja nie jest pasywną biblioteką (jak 9GAG czy Reddit), ale aktywną grą, która jako "paliwa" używa memów.

# Unikalne Cechy
1. **Podjemowanie Decyzji** - użytkownik jest zmuszony do podjęcia decyzji czy mem którego widzi jest dobry czy słaby. Jest to jedyny sposób aby zobaczyć kolejne treści. W ten sposób dostajemy informacje jakiej jakości memy są wstawiane przez użytkowników i możemy wykorzystać te informacje w innych miejscach platformy.
2. **Jakość Treści** - dzięki temu że użytkownicy segregują memy oceniając ich jakość oraz ograniczeniu pozwalającym na przesyłanie kolejnych memów dopeiro po 24 godzinach wymuszamy na uzytkownikach podejmowanie decyzji o wstawianiu jedynie wartościowych treści co automatycznie podnosi jakość matreiałów dostępnych na platformie.
3. **Grywalizacja** - używając oceny użytkowników możemy pokazywać tabele graczy z największą liczbą pozytywnych głosów. Kolejnymi filarami są: wirtualna waluta (💎diamenty), utrzymywanie dziennej serii logowania 🔥, wbijanie kolejnych poziomów profilu, zadania tygodniowe oraz unikalne odznaki oraz przedmioty kosmetyczne do dekorowania profilu.

# Podstawowa Mechanika (Game Loop)
- Codzienne logowanie i przedłużanie passy logowań.
- Przysyłanie własnych memów podlegając limitowi czasowemu (1 mem - 24 godziny)
- Ekran Główny "Dashboard": Użytkownik widzi jednego mema na pełnym ekranie.
- Swipe w Prawo "Upvote": Głos pozytywny.
- Swipe w Lewo "Downvote": Głos negatywny.
- Zdobywanie diamentów i punktów doświadczenia za głosowanie.
- Zdobywanie diamentów za wypełnianie zadań tygodniowych i kolejne poziomy.
- ✨ Wydawanie diamentów na przedmioty w sklepie i elementy kosmetyczne profilu.

# Design
- W pełni responywny design. Layout dopasowany i skalowany zarówno do ekranów pionowych jak i poziomych.
- Na telefonach mechanika "swipe" pozwala oceniać memy poprzez gesty zamiast przycisów na ekranie.
- Dark mode 😎

## Zrzuty ekranu
| Dashboard Desktop | Dashboard Mobile |
|:---:|:---:|
| ![Desktop](https://github.com/SzerokiGeralt/MemeSwipe/blob/main/DashboardDesktop.png)  | ![Mobile](https://github.com/SzerokiGeralt/MemeSwipe/blob/main/DashboardMobile.png)  |

| Store | Quests |
|:---:|:---:|
| ![Desktop](https://github.com/SzerokiGeralt/MemeSwipe/blob/main/Store.png)  | ![Mobile](https://github.com/SzerokiGeralt/MemeSwipe/blob/main/Quests.png)  |

| Profile | Badges |
|:---:|:---:|
| ![Desktop](https://github.com/SzerokiGeralt/MemeSwipe/blob/main/Profile.png)  | ![Mobile](https://github.com/SzerokiGeralt/MemeSwipe/blob/main/Badges.png)  |

| Leaders | Upload |
|:---:|:---:|
| ![Desktop](https://github.com/SzerokiGeralt/MemeSwipe/blob/main/Leaders.png)  | ![Mobile](https://github.com/SzerokiGeralt/MemeSwipe/blob/main/Upload.png)  |

## Tech stack
- PHP
- PostgreSQL
- JavaScript
- Docker

## Zaimplementowane zabezpieczenia

| PHP SECURITY BINGO | Zrealizowano |
|:---:|:---:|
| Ochrona przed SQL injection (prepared statements / brak konkatenacji SQL)  | ✅ |
| Nie zdradzam, czy email istnieje – komunikat typu „Email lub hasło niepoprawne”  | ✅ |
| Walidacja formatu email po stronie serwera  | ✅ |
| UserRepository (*Database*) zarządzany jako singleton  | ✅ |
| Logowanie i rejestracja dostępne tylko przez HTTPS  | ✅ |

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

## Diagram ERD Bazy Danych
![ERD Diagram](https://github.com/SzerokiGeralt/MemeSwipe/blob/main/ERD.png)

## Uruchomienie projektu
1. Zainstaluj Docker i Docker Compose
2. Sklonuj repozytorium i przejdź do katalogu projektu
3. Uruchom: `docker-compose up --build`
4. Aplikacja będzie dostępna pod adresem http://localhost:8080

## Autor
Karol "Szeroki Geralt" Kapusta
