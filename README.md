<p align="center">
    <img src="https://joonlabs.com/php-graphql/logo.svg" alt="index.js logo" width="300" align="center" style="width: 300px; display: block; margin-left: auto; margin-right: auto;"/>
</p>

# php-graphql

[![CI](https://github.com/joonlabs/php-graphql/actions/workflows/php.yml/badge.svg)](https://github.com/joonlabs/php-graphql/actions/workflows/php.yml)
[![Latest Stable Version](https://poser.pugx.org/joonlabs/php-graphql/v)](//packagist.org/packages/joonlabs/php-graphql)
[![License](https://poser.pugx.org/joonlabs/php-graphql/license)](//packagist.org/packages/joonlabs/php-graphql)

php-graphql is a pure php implementation of the latest GraphQL [specification](https://github.com/graphql/graphql-spec)
based on the [reference implementation in JavaScript](https://github.com/graphql/graphql-js).

## Installation

Via **composer:**
```bash
composer require joonlabs/php-graphql
```

Via **git submodule:**

```bash
git clone https://github.com/joonlabs/php-graphql.git
```

For more information, see the [doc pages](https://joonlabs.github.io/php-graphql/docs/getting-started/)

## Documentation
The library's documentation is available at [https://joonlabs.github.io/php-graphql/](https://joonlabs.github.io/php-graphql/) or in the [docs](https://github.com/joonlabs/php-graphql/tree/master/docs) folder.

## Examples
Examples can be found in the [examples](https://github.com/joonlabs/php-graphql/tree/master/docs) directory and are additionally discussed in the [documentation](https://joonlabs.github.io/php-graphql/).

## Motivation
This project was developed out of internal needs in the company. We decided to go with an own implementation to stay in control of performance critical parts, implement cache systems and support file upload from scratch.
Also this library does not use arrays but explicit parameters for initialization and configuration of types, fields and other objects. Thanks to features like named arguments which were added by PHP 8, this library achieves a high readability.
As nice sideeffect this library seems to outperform the currently most used library [webonyx/graphql-php](https://github.com/webonyx/graphql-php) in terms of speed in many use cases (please see [https://github.com/joonlabs/graphql-benchmarks](https://github.com/joonlabs/graphql-benchmarks) for reference). 
## Backers and sponsors

<img src="https://joonlabs.com/php-graphql/backers/joon.png" alt="index.js logo" height="30"/><br>
see [joonlabs.com](https://joonlabs.com)
<br>
<br>
<img src="https://joonlabs.com/php-graphql/backers/leafx.png" alt="index.js logo" height="30"/><br>
see [leafx.de](https://leafx.de)


## License
Fore more infromation regarding the license, see the [LICENSE](https://github.com/joonlabs/php-graphql/blob/master/LICENSE) file.