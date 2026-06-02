@echo off
powershell -Command "Expand-Archive -Path '%~2' -DestinationPath '%~4' -Force"
