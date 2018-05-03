3b-fly's PHP library
===========

```
This project is not stable nor documented yet
```

### Packages and Classes

- **environment**
  - `bbbfly_Arguments` - _Provides access to parsed command line arguments._
  - `bbbfly_INI` - _Allows to get parsed configuration option value._
- **common**
  - `bbbfly_Config` - _INI, JSON and XML configuration files handler._
  - `bbbfly_MIME` - _Translates file extension to corresponding MIME type._
  - `bbbfly_Require` - _Provides easy way to require files from different locations._
  - `bbbfly_Upload` - _Handles file upload._
- **adodb**
  - `bbbfly_ADOdb_Connection` - _ADOdb database connection factory._
- **RPC**
  - `bbbfly_RPC` - _Rich HTTP RPC handler._
  - `bbbfly_RPC_DB` - _bbbfly_RPC extension providing bbbfly_ADOdb_Connection._
- **geoPHP**
  - `bbbfly_geoPHP` - _geoPHP extension with geometry collection support._
- **controlsjs**
  - `bbbfly_CookieSettings` - _Converts Controls.js cookie settings to client settings._
<br/>
<br/>

> ### License
> Comply with [GPLv3 license](http://www.gnu.org/licenses/gpl-3.0.html) for non-commercial use.<br/>
> License for commercial use is to be purchased at [purchase@3b-fly.eu](mailto:purchase@3b-fly.eu).
>
> ### Third party licenses
> Some packages contain third party code which falls under its own license.<br/>
> Verify that you satisfy that third party license conditions before using listed packages.<br/>
>
>| package  | library                                    | version | license                 |
>| -------- | ------------------------------------------ | ------- | ----------------------- |
>| adodb    | [ADOdb](https://github.com/ADOdb/ADOdb)    | 5.20.12 | BSD 3-Clause / LGPLv2.1 |
>| geoPHP   | [geoPHP](https://github.com/phayes/geoPHP) | 1.2     | Modified BSD / GPLv2    |
