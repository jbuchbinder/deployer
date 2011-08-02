#!/bin/sh

ssh root@${1} grep -v '^#' $2 >  $3
