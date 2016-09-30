#! /usr/bin/perl 

$c=0;
while(<>) { 
		
#	if(/ --pid:\d+(.*)/) { 
#			$c++; 
#			print "$1 \n" ; 
#		} 

s/\d+/_X_/g ; 
$c++; 
unless (/--vars/) { 
	print ; 
} 


	#	exit if($c > 100) ; 

}
