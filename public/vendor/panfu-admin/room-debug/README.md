# Room debug overlays

Pliki SVG i `manifest.json` są wygenerowanymi warstwami `walkarea` publicznych pokoi Panfu. Aplikacja nie uruchamia dekompilatora podczas obsługi żądania.

Po zmianie plików `room.swf` należy odświeżyć warstwy poleceniem:

```bash
php artisan panfu:generate-room-debug-assets /ścieżka/do/ffdec.jar
```

Generator używa trybu CLI [JPEXS Free Flash Decompiler](https://github.com/jindrapetrik/jpexs-decompiler/wiki/Commandline-arguments), usuwa powtarzające się klatki i zapisuje położenie każdej unikalnej warstwy w układzie sceny 772 × 480.
