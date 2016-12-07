#!/bin/bash
rm -rf tests/_output/coverage/
vendor/bin/codecept $@ --coverage-html
