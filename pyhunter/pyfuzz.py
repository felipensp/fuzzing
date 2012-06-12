import types
import sys
import zlib
import hmac
import getopt
import re
import string
import md5
import io
import getopt
import logging
import curses
import mmap
import json
import uu
import quopri
import rfc822
import ssl
import readline
import platform
import ctypes
import csv
import gzip
import xdrlib
import sha
import time
import asyncore
import urllib
import urllib2
import gettext
import wave
#import webbrowser
import binascii
import HTMLParser
import sgmllib
import xml
import base64
import mimify
import mailcap
import fnmatch
import marshal
import shelve
import numbers
import heapq
import textwrap
import struct
import cmath
import math
import decimal
import itertools
import locale

args = [
	"-1",
	"0",
	"9223372036854775807",
	"9223372036854770000",
	"9223372036854775806",
	"~9223372036854775807",
	"'"+"a"*10000+"'",
	"'\0'",
	"[f for f in [1,2,3]]",
	"[" + "[1, 3, 4]" * 10 + "]",
	"'\xE0\x81\xA1'*10000",
	"(1)",
	"{'foo': 100, 'bar': []}",
	"lambda x: x + 1",
	"1, 2, 3"
	]

# print sys.modules.keys()
# sys.exit()

modules = sys.modules.keys()
#modules = ["decimal"]

ignore = ["sys.exit", "ctypes._string_at", "ctypes._wstring_at",
		  "ctypes.memmove", "ctypes.memset", "ctypes.string_at",
		  "ctypes._wstring_at", "ctypes.wstring_at", "urllib2.randombytes",
		  "sgmllib.test", "decimal._dexp", "decimal._dlog", "decimal._dlog10",
		  "decimal._log10_digits", "math.factorial"]

for module in modules:
	print ">> Testing " + module
	
	try:
		funcs = dir(globals()[module])
	
		for func in funcs:
			fullname = module + "." + func
		
			print ">> Testing function " + fullname
			
			if fullname in ignore:
				continue
				
			for arg in args:
				for n in range(1, 5):
					try:
						#print fullname + "(" + ','.join([arg]*n) + ")"
						eval(fullname + "(" + ','.join([arg]*n) + ")")
					except Exception as err:
						print "Error: %s" % err
			print "-" * 50
	except Exception as err:
		print "Error: %s" % err
