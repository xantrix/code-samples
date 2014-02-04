package update;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.PrintWriter;
import java.net.Socket;
import java.net.UnknownHostException;

public class updateServerThread extends Thread {

    private Socket socket = null;
    private PrintWriter log = null;
    String chost = "unknown";
    String cip = "unknown";

    public updateServerThread(Socket socket) {
 	   super("updateServerThread");
 	   this.socket = socket;
 	   this.log = updateServer.log;

 	   try{
 	   chost = this.socket.getInetAddress().getLocalHost().getHostName();
 	   cip = this.socket.getInetAddress().getLocalHost().getHostAddress();
 	   }
  	  catch(UnknownHostException e){
 		   System.err.println("lookup Error ");
 	   }

    }
    public String getTime(){
 	   return updateServer.getTime();
    }

    public void run() {
 	   log.println(getTime()+" Client connesso:"+chost+":"+cip);
 	   log.flush();
 	   System.out.println(" Client connesso:"+chost+":"+cip);

 	try{
        PrintWriter out = new PrintWriter(socket.getOutputStream(), true);
        BufferedReader in = new BufferedReader(
 			   new InputStreamReader(
 					   socket.getInputStream()));

        String inputLine, outputLine;

        updateProtocol kkp = new updateProtocol();

        //prima connessione
        outputLine = kkp.processInput(null);
        log.println(outputLine);
        log.flush();
        out.println(outputLine);

 	       if ((inputLine = in.readLine()) != null) {

 	    	   outputLine = kkp.processInput(inputLine);

 	            log.println(outputLine);
 	            log.flush();

 	            out.println(outputLine);

 	       }

 	       out.close();
 	       in.close();
 	       socket.close();
 	       log.println(getTime()+" Client disconnesso:"+chost+":"+cip);
 	       log.flush();
        }
        catch(Exception e){
     	   log.println(getTime()+" Client disconnesso:"+chost+":"+cip);
     	   log.flush();
     	   System.err.println(" Client disconnesso:"+chost+":"+cip);
        }


    }//run
}//class
