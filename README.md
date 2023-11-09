We need to create a custom filter for API Platform 3 which is running on Symfony 6.

You need to create a one custom filter for Symfony 6 API Platform

It should extend `\ApiPlatform\Doctrine\Orm\Filter\AbstractFilter`

Similar what they did in the documentation https://api-platform.com/docs/guide/create-a-custom-doctrine-filter/

We should be able to use this filter in our entities like this: `?page=1&firstname[exact]=mick`

