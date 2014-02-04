package update;

import java.io.BufferedReader;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.text.SimpleDateFormat;
import java.util.GregorianCalendar;


public class Update {

	static SimpleDateFormat sdf =	new SimpleDateFormat("dd/MM/yyyy - HH:mm:ss");

    public static String call(String batch){

 	   String[] batchs = new String[]{batch};
 	   return execute(batchs);
    }


    public static String execute(String[] batchs){

 	   if(batchs == null) return "batchs vuoto";
 	   String output = "";
 	   Runtime r=Runtime.getRuntime();
 	   Process p=null;
 	   String path = "c:/batchnew/";


 	   int n = batchs.length;

 	   for(int i=0; i<n; i++)
 	   {

 		   String[] tmp = batchs[i].split(":");

 		   String bat = tmp[0];
 		   String batlog = "";
 		   String param = "";

 		   if(tmp.length>1)
 			   batlog = tmp[1];

 		   if(tmp.length>2)
 			   for(int j=2;j<tmp.length;j++)
 				   param += " "+tmp[j];



 		   try{

 			   output += sdf.format( new GregorianCalendar().getTime() )+" Execing "+bat+param;
 			   p = r.exec(new String[]{"cmd","/c",path+bat+param});

 			   output +="\r\n Exit Value = " + p.waitFor();//wait until the external program finish
 			   output +="\r\n";


 			   if(!"".equals(batlog))
 			   {
 				 BufferedReader br = null;
 				 FileReader fr = null;
 				 output +="\r\n<"+batlog+">\r\n";
 					   try{
 							fr = new FileReader(path+batlog);
 							br = new BufferedReader(fr);
 						   String line = null;

 						       while ( (line = br.readLine()) != null)
 						       {
 						    	   output +=line+"\r\n";
 							   }
 						 }
 						 catch (FileNotFoundException e)
 						 {    output +=e.getMessage();    }

 				 output +="\r\n</"+batlog+">\r\n";
 				 fr.close();
 				 br.close();

 			  }

 		   }
 		   catch(Exception e)
 		   {   output +=e.getMessage();
 		   }
 	   }

 	   return output;

    }//update


    /*public static void main (String[] args){
 	   Update u = new Update();

    }*/


}//class
