"""Convert the images to the right sizes.

This converts the size of the images in "OriginalStims," and saves them
elsewhere.
"""

from PIL import Image
import os
import glob


def resizeIt(fl, saveDir):
    imHeight = 300.0

    print(fl)
    im = Image.open(f)
    imH, imW = im.size
    imNewW = int(imHeight / imH * imW)
    imNewH = int(imHeight)

    im = im.resize((imNewH, imNewW))
    flname = f.split('/')[-1]
    im.save(os.path.join(saveDir, flname))


bf = glob.glob('./BF/*.png')
wf = glob.glob('./WF/*.png')
df = glob.glob('./Dems/*.png')
rf = glob.glob('./Reps/*.png')

for f in bf:
    #resizeIt(f, "BFResizedSmall")
    pass

for f in wf:
    #resizeIt(f, "WFResizedSmall")
    pass

for f in df:
    resizeIt(f, "DemsResizedSquare")

for f in rf:
    resizeIt(f, "RepsResizedSquare")