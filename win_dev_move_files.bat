chcp 65001
cd  %~dp0
if not exist %~dp0public\static (
    mkdir %~dp0public\static
)
cd .\apps
echo 当前路径：%~dp0
for /d %%i in (*) do (
    if  exist ".\%%i\view\static\" (
        XCOPY /E /Y ".\%%i\view\static\" "%~dp0public\static"
    )
)




