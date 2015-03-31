#! /usr/bin/perl 

while(<>) { 

	if (/.*(->.*?)(->.*?)->variable_get/) { 

		print "variable_get .. $2 .. $1 \n"; 

	} 
}
