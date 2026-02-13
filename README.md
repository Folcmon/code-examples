# code-examples
Contains examples of my code experience

## Notification Module

**Kontekst biznesowy:**
Moduł odpowiada za pobieranie historii i statystyk powiadomień email wysłanych do klientów w kontekście konkretnych zamówień. Integruje się z dwoma zewnętrznymi SDK: OrderProvider (dane zamówień) oraz NotificationSdk (historia powiadomień).

**Architektura:**
- **CQRS + Clean Architecture** - podział na warstwy Application/Domain/Infrastructure/Presentation
- **Query Handler Pattern** - `GetNotificationCountHandler` obsługuje zapytania przez QueryBus
- **Dependency Injection** - użycie interfejsów, Symfony Autowire i readonly properties (PHP 8.1+)

**Kluczowe elementy:**
1. **API Endpoint** (`NotificationController`) - REST endpoint `/api/orders/{orderId}/notifications-info` z walidacją UUID
2. **Provider Chain** - `OrderProvider` → `MailNotificationStatsProvider` → zewnętrzne SDK
3. **Mapper** - mapowanie kodów kurierów między różnymi systemami (np. INPOST → InPost, PEKAES → Pallex)
4. **Error Handling** - dedykowany `OrderProviderException` z mapowaniem błędów SDK na HTTP status codes (401/403/404/503)
5. **Immutable Entities** - readonly klasy jako value objects (Order, Query, Views)

**Przepływ danych:**
```
HTTP Request → Controller → QueryBus → Handler → MailNotificationStatsProvider 
  → OrderProvider (SDK) → pobierz Order (tracking number, courier)
  → mapuj nazwę kuriera → NotificationSdk → zwróć agregowane statystyki
```

**Testowanie:**
- PHPUnit testy (`tests/`) z mockami SDK
- PHPStan do analizy statycznej (`phpstan.dist.neon`)
- CI scripts (`ci/run_ci_*.sh`)
