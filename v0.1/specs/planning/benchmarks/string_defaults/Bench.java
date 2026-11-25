import java.nio.file.*;
import java.nio.charset.StandardCharsets;
public class Bench {
    static volatile Object sink;
    static volatile String input;
    static long[] batch(String op,String s,String t,String needle,String repl,long count) {
        input=s;
        long sum=0, start=System.nanoTime();
        if(op.equals("replace")) {
            for(long i=0;i<count;i++) {String out=input.replace(needle,repl); sum+=out.length(); sink=out;}
        } else if(op.equals("compare")) {
            for(long i=0;i<count;i++) sum+=1+Integer.signum(input.compareTo(t));
        } else {
            for(long i=0;i<count;i++) sum+=input.length();
        }
        return new long[]{System.nanoTime()-start,sum};
    }
    public static void main(String[] args) throws Exception {
        for(String line:Files.readAllLines(Path.of(args[0]),StandardCharsets.UTF_8)) {
            String[] f=line.split("\t",-1);
            String op=f[1],s=f[2],t=new String(f[3].toCharArray()),n=f[4],r=f[5];
            if(op.equals("replace") && !s.replace(n,r).equals(f[6])) throw new Exception("replacement mismatch");
            if(op.equals("compare") && Integer.signum(s.compareTo(t))!=Integer.parseInt(f[6])) throw new Exception("comparison mismatch");
            // Timed batches exclude startup. Give HotSpot a bounded per-case warmup.
            long until=System.nanoTime()+500_000_000;
            while(System.nanoTime()<until) sink=batch(op,s,t,n,r,128);
            long count=1;
            while(batch(op,s,t,n,r,count)[0]<20_000_000 && count<(1<<26)) count*=2;
            for(int trial=0;trial<7;trial++) {long[] b=batch(op,s,t,n,r,count); System.out.printf("%s,java,%d,%d,%d,%d%n",f[0],trial,count,b[0],b[1]);}
        }
    }
}
